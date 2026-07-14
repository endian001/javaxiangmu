<?php

namespace App\Admin\Actions\Grid\User;

use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use App\Models\TransferLog;
use App\Models\UserOperateLog;
use App\Models\User_Api;
use App\Services\TgService;
use App\User;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackBalance extends RowAction
{
    const STATE_FAILED = 0;
    const STATE_SUCCESS = 1;
    const STATE_PENDING = 3;
    const STATE_EXTERNAL_SUCCESS_LOCAL_PENDING = 4;
    const STATE_UNKNOWN_RECONCILE = 5;

    const STATUS_PENDING_BALANCE = 'pending_balance';
    const STATUS_CALLING = 'calling';
    const STATUS_EXTERNAL_SUCCESS_LOCAL_PENDING = 'external_success_local_pending';
    const STATUS_UNKNOWN_RECONCILE = 'unknown_reconcile';
    const STATUS_SUCCESS = 'success';
    const STATUS_EXTERNAL_FAILED = 'external_failed';
    const STATUS_BALANCE_FAILED = 'balance_failed';
    const STATUS_NO_BALANCE = 'no_balance';

    const ACTIVE_STATUSES = [
        self::STATUS_PENDING_BALANCE,
        self::STATUS_CALLING,
        self::STATUS_EXTERNAL_SUCCESS_LOCAL_PENDING,
        self::STATUS_UNKNOWN_RECONCILE,
    ];

    protected $title = '一键回收';

    public function handle(Request $request)
    {
        $id = $this->getKey();
        $tg = new TgService();
        $user = User::find($id);
        if (!$user) {
            return $this->response()->error('用户不存在')->refresh();
        }

        $sourceLog = TransferLog::where('user_id', $user->id)
            ->where('transfer_type', 0)
            ->orderBy('id', 'desc')
            ->first();
        if (!$sourceLog || !$sourceLog->api_type) {
            return $this->response()->success('没有可回收的金额')->refresh();
        }

        $platform = (string) $sourceLog->api_type;
        $pending = $this->createPendingRecovery($user->id, $platform);
        if (!$pending['created']) {
            return $this->continueExistingRecovery($pending['log'], $tg, $request);
        }

        $log = $pending['log'];
        $balanceResult = $tg->balance($platform, $user->username);
        if (($balanceResult['code'] ?? 201) != 200) {
            $this->markRecovery(
                $log->id,
                self::STATE_FAILED,
                self::STATUS_BALANCE_FAILED,
                'failed',
                $this->messageFromResult($balanceResult)
            );

            return $this->response()->error($this->messageFromResult($balanceResult))->refresh();
        }

        $rawBalance = $balanceResult['data'] ?? 0;
        $amount = floor((float) $rawBalance);
        if ($amount < 1) {
            $this->markRecovery(
                $log->id,
                self::STATE_FAILED,
                self::STATUS_NO_BALANCE,
                'success',
                '上游余额不足 1 元，balance='.$rawBalance,
                [
                    'money' => 0,
                    'real_money' => 0,
                ]
            );
            $this->syncUserApiBalance($user, $platform, (float) $rawBalance);

            return $this->response()->success('没有可回收的金额')->refresh();
        }

        $this->markRecovery(
            $log->id,
            self::STATE_PENDING,
            self::STATUS_CALLING,
            'calling',
            '准备调用上游回收，amount='.$amount,
            [
                'money' => $amount,
                'real_money' => $amount,
                'cash_fee' => 0,
            ]
        );

        $withdrawResult = $tg->withdrawal($user->username, $amount, $log->order_no, $platform);
        if (($withdrawResult['code'] ?? 201) == 200) {
            $this->markRecovery(
                $log->id,
                self::STATE_EXTERNAL_SUCCESS_LOCAL_PENDING,
                self::STATUS_EXTERNAL_SUCCESS_LOCAL_PENDING,
                'success',
                '上游回收成功，等待本地入账'
            );

            try {
                $posted = $this->postExternalSuccess($log->id, $amount, $request);
            } catch (\Throwable $e) {
                $this->markRecovery(
                    $log->id,
                    self::STATE_UNKNOWN_RECONCILE,
                    self::STATUS_UNKNOWN_RECONCILE,
                    'success',
                    '上游已成功，本地入账失败：'.$e->getMessage()
                );

                return $this->response()->error('上游已回收成功，本地入账失败，已进入待对账：'.$log->order_no)->refresh();
            }

            return $this->response()->success('回收成功：'.$posted.'元')->refresh();
        }

        return $this->handleWithdrawalFailure($log, $amount, $platform, $tg, $request, $withdrawResult);
    }

    protected function createPendingRecovery($userId, $platform)
    {
        return DB::transaction(function () use ($userId, $platform) {
            $user = User::where('id', $userId)->lockForUpdate()->first();
            if (!$user) {
                throw new \RuntimeException('用户不存在');
            }

            $existing = $this->activeRecoveryQuery($user->id, $platform)->lockForUpdate()->first();
            if ($existing) {
                return ['created' => false, 'log' => $existing];
            }

            $orderNo = $this->makeOrderNo($user->id);
            $log = TransferLog::create([
                'order_no' => $orderNo,
                'api_type' => $platform,
                'platform_type' => $platform,
                'user_id' => $user->id,
                'transfer_type' => 1,
                'money' => 0,
                'cash_fee' => 0,
                'real_money' => 0,
                'before_money' => $user->balance,
                'after_money' => $user->balance,
                'state' => self::STATE_PENDING,
                'remark' => 'admin_back_balance',
                'recovery_key' => 'back_balance:'.$orderNo,
                'recovery_status' => self::STATUS_PENDING_BALANCE,
                'external_status' => 'pending',
                'external_checked_at' => now(),
                'reconcile_note' => '等待查询上游平台余额',
            ]);

            return ['created' => true, 'log' => $log];
        });
    }

    protected function continueExistingRecovery(TransferLog $log, TgService $tg, Request $request)
    {
        if ($log->recovery_status === self::STATUS_EXTERNAL_SUCCESS_LOCAL_PENDING) {
            try {
                $posted = $this->postExternalSuccess($log->id, (float) $log->money, $request);
                return $this->response()->success('已完成待入账回收：'.$posted.'元')->refresh();
            } catch (\Throwable $e) {
                $this->markRecovery(
                    $log->id,
                    self::STATE_UNKNOWN_RECONCILE,
                    self::STATUS_UNKNOWN_RECONCILE,
                    'success',
                    '待入账处理失败：'.$e->getMessage()
                );

                return $this->response()->error('待入账处理失败，已进入待对账：'.$log->order_no)->refresh();
            }
        }

        if ($log->recovery_status === self::STATUS_UNKNOWN_RECONCILE || $this->isStaleCalling($log)) {
            return $this->reconcileExistingOrder($log, $tg, $request);
        }

        if ($log->recovery_status === self::STATUS_PENDING_BALANCE && $this->isStalePendingBalance($log)) {
            $this->markRecovery(
                $log->id,
                self::STATE_FAILED,
                self::STATUS_BALANCE_FAILED,
                'failed',
                '余额查询前置状态超时，允许重新发起回收'
            );

            return $this->response()->error('上一次回收在余额查询前超时，已释放，请重新点击回收')->refresh();
        }

        return $this->response()->success('已有回收处理中，订单号：'.$log->order_no)->refresh();
    }

    protected function handleWithdrawalFailure(TransferLog $log, $amount, $platform, TgService $tg, Request $request, array $withdrawResult)
    {
        $status = $this->queryExternalStatus($tg, $log->order_no, $platform);
        if ($status['status'] === 'success') {
            $this->markRecovery(
                $log->id,
                self::STATE_EXTERNAL_SUCCESS_LOCAL_PENDING,
                self::STATUS_EXTERNAL_SUCCESS_LOCAL_PENDING,
                'success',
                'withdrawal返回失败但orderstatus确认成功：'.$status['note']
            );

            try {
                $posted = $this->postExternalSuccess($log->id, $amount, $request);
                return $this->response()->success('回收成功：'.$posted.'元')->refresh();
            } catch (\Throwable $e) {
                $this->markRecovery(
                    $log->id,
                    self::STATE_UNKNOWN_RECONCILE,
                    self::STATUS_UNKNOWN_RECONCILE,
                    'success',
                    '上游成功，本地入账失败：'.$e->getMessage()
                );

                return $this->response()->error('上游已回收成功，本地入账失败，已进入待对账：'.$log->order_no)->refresh();
            }
        }

        if ($status['status'] === 'failed') {
            $this->markRecovery(
                $log->id,
                self::STATE_FAILED,
                self::STATUS_EXTERNAL_FAILED,
                'failed',
                '上游回收失败：'.$this->messageFromResult($withdrawResult).'; orderstatus='.$status['note']
            );

            return $this->response()->error($this->messageFromResult($withdrawResult))->refresh();
        }

        $this->markRecovery(
            $log->id,
            self::STATE_UNKNOWN_RECONCILE,
            self::STATUS_UNKNOWN_RECONCILE,
            'unknown',
            '上游回收结果不确定：'.$this->messageFromResult($withdrawResult).'; orderstatus='.$status['note']
        );

        return $this->response()->error('上游回收结果暂不确定，已进入待对账：'.$log->order_no)->refresh();
    }

    protected function reconcileExistingOrder(TransferLog $log, TgService $tg, Request $request)
    {
        $status = $this->queryExternalStatus($tg, $log->order_no, (string) $log->api_type);
        if ($status['status'] === 'success') {
            $this->markRecovery(
                $log->id,
                self::STATE_EXTERNAL_SUCCESS_LOCAL_PENDING,
                self::STATUS_EXTERNAL_SUCCESS_LOCAL_PENDING,
                'success',
                '对账确认上游成功：'.$status['note']
            );

            try {
                $posted = $this->postExternalSuccess($log->id, (float) $log->money, $request);
                return $this->response()->success('对账入账成功：'.$posted.'元')->refresh();
            } catch (\Throwable $e) {
                $this->markRecovery(
                    $log->id,
                    self::STATE_UNKNOWN_RECONCILE,
                    self::STATUS_UNKNOWN_RECONCILE,
                    'success',
                    '对账确认成功但本地入账失败：'.$e->getMessage()
                );

                return $this->response()->error('对账确认上游成功，但本地入账失败：'.$log->order_no)->refresh();
            }
        }

        if ($status['status'] === 'failed') {
            $this->markRecovery(
                $log->id,
                self::STATE_FAILED,
                self::STATUS_EXTERNAL_FAILED,
                'failed',
                '对账确认上游失败：'.$status['note']
            );

            return $this->response()->error('对账确认上游订单失败，已释放回收入口')->refresh();
        }

        $this->markRecovery(
            $log->id,
            self::STATE_UNKNOWN_RECONCILE,
            self::STATUS_UNKNOWN_RECONCILE,
            'unknown',
            '对账仍无法确认：'.$status['note']
        );

        return $this->response()->error('对账仍无法确认，请稍后重试或联系上游核单：'.$log->order_no)->refresh();
    }

    protected function postExternalSuccess($logId, $amount, Request $request)
    {
        if ((float) $amount <= 0) {
            throw new \RuntimeException('回收金额无效');
        }

        return DB::transaction(function () use ($logId, $amount, $request) {
            $log = TransferLog::where('id', $logId)->lockForUpdate()->first();
            if (!$log) {
                throw new \RuntimeException('回收流水不存在');
            }

            if ($log->posted_at || $log->recovery_status === self::STATUS_SUCCESS) {
                return (float) $log->real_money;
            }

            $user = User::where('id', $log->user_id)->lockForUpdate()->first();
            if (!$user) {
                throw new \RuntimeException('用户不存在');
            }

            $beforeBalance = (float) $user->balance;
            $afterBalance = $beforeBalance + (float) $amount;
            $user->balance = $afterBalance;
            $user->save();

            $log->money = $amount;
            $log->real_money = $amount;
            $log->before_money = $beforeBalance;
            $log->after_money = $afterBalance;
            $log->state = self::STATE_SUCCESS;
            $log->recovery_status = self::STATUS_SUCCESS;
            $log->external_status = 'success';
            $log->posted_at = now();
            $log->external_checked_at = now();
            $log->reconcile_note = $this->appendNote($log->reconcile_note, '本地入账成功，amount='.$amount);
            $log->save();

            $this->decreaseUserApiBalance($user, (string) $log->api_type, (float) $amount);
            $this->writeAuditLog($user, $log, $beforeBalance, $afterBalance, $amount, $request);

            return (float) $amount;
        });
    }

    protected function activeRecoveryQuery($userId, $platform)
    {
        return TransferLog::where('user_id', $userId)
            ->where('api_type', $platform)
            ->where('transfer_type', 1)
            ->whereIn('recovery_status', self::ACTIVE_STATUSES)
            ->orderBy('id', 'desc');
    }

    protected function markRecovery($logId, $state, $status, $externalStatus, $note, array $attributes = [])
    {
        DB::transaction(function () use ($logId, $state, $status, $externalStatus, $note, $attributes) {
            $log = TransferLog::where('id', $logId)->lockForUpdate()->first();
            if (!$log) {
                throw new \RuntimeException('回收流水不存在');
            }

            foreach ($attributes as $key => $value) {
                $log->{$key} = $value;
            }
            $log->state = $state;
            $log->recovery_status = $status;
            $log->external_status = $externalStatus;
            $log->external_checked_at = now();
            $log->reconcile_note = $this->appendNote($log->reconcile_note, $note);
            $log->save();
        });
    }

    protected function decreaseUserApiBalance(User $user, $platform, $amount)
    {
        $userApi = User_Api::where('api_code', $platform)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if (!$userApi) {
            User_Api::create([
                'user_id' => $user->id,
                'api_user' => $user->username,
                'api_pass' => '123456',
                'api_code' => $platform,
                'api_money' => 0,
            ]);
            return;
        }

        $userApi->api_user = $userApi->api_user ?: $user->username;
        $userApi->api_pass = $userApi->api_pass ?: '123456';
        $userApi->api_money = max(0, (float) $userApi->api_money - (float) $amount);
        $userApi->save();
    }

    protected function syncUserApiBalance(User $user, $platform, $balance)
    {
        DB::transaction(function () use ($user, $platform, $balance) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return;
            }

            $userApi = User_Api::where('api_code', $platform)
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            if (!$userApi) {
                User_Api::create([
                    'user_id' => $lockedUser->id,
                    'api_user' => $lockedUser->username,
                    'api_pass' => '123456',
                    'api_code' => $platform,
                    'api_money' => $balance,
                ]);
                return;
            }

            $userApi->api_user = $userApi->api_user ?: $lockedUser->username;
            $userApi->api_pass = $userApi->api_pass ?: '123456';
            $userApi->api_money = $balance;
            $userApi->save();
        });
    }

    protected function queryExternalStatus(TgService $tg, $orderNo, $platform)
    {
        try {
            $res = $tg->orderstatus($orderNo, $platform);
        } catch (\Throwable $e) {
            return ['status' => 'unknown', 'note' => 'orderstatus异常：'.$e->getMessage()];
        }

        $status = $this->classifyOrderStatus($res);
        return [
            'status' => $status,
            'note' => $this->rawNote($res),
        ];
    }

    protected function classifyOrderStatus($res)
    {
        if (!is_array($res)) {
            return 'unknown';
        }

        $code = (string) ($res['Code'] ?? ($res['code'] ?? ''));
        $message = (string) ($res['Message'] ?? ($res['message'] ?? ''));
        if ($code !== '' && $code !== '0' && $code !== '200') {
            return preg_match('/不存在|not\s*found|no\s*order|失败|fail/i', $message) ? 'failed' : 'unknown';
        }

        $data = $res['Data'] ?? ($res['data'] ?? []);
        if (!is_array($data)) {
            return 'unknown';
        }

        foreach (['status', 'state', 'orderStatus', 'order_status', 'result', 'transferStatus', 'transfer_status'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = strtolower(trim((string) $data[$key]));
            if (in_array($value, ['1', 'success', 'succeed', 'succeeded', 'completed', 'complete', 'ok', '成功'], true)) {
                return 'success';
            }
            if (in_array($value, ['2', 'fail', 'failed', 'failure', 'reject', 'rejected', 'cancel', 'cancelled', '失败'], true)) {
                return 'failed';
            }
            if (in_array($value, ['pending', 'processing', 'calling', '处理中', '待处理'], true)) {
                return 'pending';
            }
        }

        return 'unknown';
    }

    protected function writeAuditLog(User $user, TransferLog $log, $beforeBalance, $afterBalance, $amount, Request $request)
    {
        $auditInfo = json_encode([
            'action' => 'admin_back_balance',
            'platform' => $log->api_type,
            'order_no' => $log->order_no,
            'amount' => $amount,
            'before_balance' => $beforeBalance,
            'after_balance' => $afterBalance,
            'recovery_status' => $log->recovery_status,
        ], JSON_UNESCAPED_UNICODE);

        UserOperateLog::insertLog(
            $user->id,
            7,
            (string) $request->userAgent(),
            (string) $request->ip(),
            '',
            '管理员一键回收【'.$user->username.'】平台余额，平台'.$log->api_type.'，订单'.$log->order_no.'，金额'.$amount.'，调整前金额'.$beforeBalance.'，调整后金额'.$afterBalance,
            $auditInfo === false ? '' : $auditInfo
        );

        OpsChangeAudit::writeAdminAudit(
            'member.balance.recover',
            'member_balance',
            'Recover upstream balance for '.$user->username.' order '.$log->order_no,
            [
                'user_id' => $user->id,
                'username' => $user->username,
                'platform' => $log->api_type,
                'order_no' => $log->order_no,
                'amount' => $amount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'recovery_status' => $log->recovery_status,
            ],
            $request
        );
    }

    protected function makeOrderNo($userId)
    {
        return 'BB'.date('YmdHis').$userId.rand(100000, 999999);
    }

    protected function messageFromResult($result)
    {
        if (is_array($result)) {
            return (string) ($result['message'] ?? ($result['Message'] ?? '上游接口失败'));
        }

        return '上游接口失败';
    }

    protected function rawNote($value)
    {
        $note = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($note === false) {
            $note = '';
        }

        return $this->limitText($note, 800);
    }

    protected function appendNote($current, $note)
    {
        $line = '['.date('Y-m-d H:i:s').'] '.$note;
        $text = trim((string) $current);
        $text = $text === '' ? $line : $text."\n".$line;

        return $this->limitText($text, 2000, true);
    }

    protected function limitText($text, $length, $tail = false)
    {
        if (function_exists('mb_substr')) {
            return $tail ? mb_substr($text, -$length) : mb_substr($text, 0, $length);
        }

        return $tail ? substr($text, -$length) : substr($text, 0, $length);
    }

    protected function isStaleCalling(TransferLog $log)
    {
        if ($log->recovery_status !== self::STATUS_CALLING) {
            return false;
        }

        return strtotime((string) $log->updated_at) < time() - 120;
    }

    protected function isStalePendingBalance(TransferLog $log)
    {
        if ($log->recovery_status !== self::STATUS_PENDING_BALANCE) {
            return false;
        }

        return strtotime((string) $log->updated_at) < time() - 120;
    }

    public function confirm()
    {
        return ['确定一键回收', '系统将创建待处理回收单，防止重复点击和未入账风险。'];
    }

    protected function authorize($user): bool
    {
        return OperationPermission::can(OperationPermission::MEMBER_BALANCE_RECOVER, $user);
    }

    protected function parameters()
    {
        return [];
    }
}
