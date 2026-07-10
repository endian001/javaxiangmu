<?php

namespace App\Admin\Forms;

use App\Admin\Support\OperationPermission;
use App\Models\TransferLog;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Widgets\Form;
use App\Models\Users;
use App\Models\UserOperateLog;
use Illuminate\Support\Facades\DB;

class Userbalance extends Form implements LazyRenderable
{
    use LazyWidget; // 使用异步加载功能
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        try {
            OperationPermission::assert(OperationPermission::MEMBER_BALANCE_ADJUST);
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage());
        }

        $id = $this->payload['id'] ?? null;

        $balance = $input['balance'] ?? 0;
        $source = trim((string) ($input['balance_source'] ?? ''));
        if (! $id) {
            return $this->response()->error('参数错误');
        }

        if (!is_numeric($balance)) {
            return $this->response()->error('金额输入错误');
        }

        $balance = (float) $balance;
        if ($balance == 0.0) {
            return $this->response()->error('调整金额不能为0');
        }

        if ($source === '') {
            return $this->response()->error('请填写资金来源');
        }

        try {
            DB::transaction(function () use ($id, $balance, $source) {
                $user = Users::query()->where('id', $id)->lockForUpdate()->first();
                if (! $user) {
                    throw new \RuntimeException('用户不存在');
                }

                $beforeBalance = (float) $user->balance;
                $afterBalance = $beforeBalance + $balance;
                if ($afterBalance < 0) {
                    throw new \RuntimeException('账户余额不足，无法完成扣除操作');
                }

                TransferLog::create([
                    'order_no' => time().rand(1000,9999),
                    'api_type' => 'web',
                    'user_id' => $user->id,
                    'transfer_type' => ($balance < 0) ? 4 : 3,
                    'money' => $balance,
                    'cash_fee' => 0,
                    'real_money' => abs($balance),
                    'before_money' => $beforeBalance,
                    'after_money' => $afterBalance,
                    'state' => 1,
                    'remark' => $source,
                ]);

                $user->balance = $afterBalance;
                $user->save();

                $request = request();
                $auditInfo = json_encode([
                    'action' => 'admin_balance_adjust',
                    'source' => $source,
                    'amount' => $balance,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                ], JSON_UNESCAPED_UNICODE);

                UserOperateLog::insertLog(
                    $user->id,
                    7,
                    (string) $request->userAgent(),
                    (string) $request->ip(),
                    '',
                    '管理员调整【' . $user->username . '】账户余额，调整金额数'.$balance.'，调整前金额'.$beforeBalance.'，调整后金额'.$afterBalance,
                    $auditInfo === false ? '' : $auditInfo
                );
            });
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage());
        }
 

        return $this->response()->success('账户余额调整成功')->refresh();

    }

    /**
     * Build a form here.
     */
    public function form()
    {
        //$this->confirm('您确定要调整余额吗', 'content');
        $this->text('balance','调整金额')->rules('required')->default(0.00)->help('输入调整金额，整数为增加，负数为扣除');
        $this->text('balance_source','资金来源')->rules('required');
    }
}
