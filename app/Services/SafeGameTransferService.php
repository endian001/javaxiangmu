<?php

namespace App\Services;

use App\Models\TransferLog;
use App\Models\User;
use App\Models\User_Api;
use Illuminate\Support\Facades\DB;

class SafeGameTransferService
{
    public function autoMoveToPlatform(User $user, $platform, TgService $tg)
    {
        $platform = strtoupper(trim((string) $platform));
        if ($platform === '') {
            return ['code' => 201, 'message' => 'game platform is required'];
        }

        $freshUser = User::where('id', $user->id)->first();
        if (!$freshUser) {
            return ['code' => 201, 'message' => 'login expired'];
        }

        $lastPlatform = TransferLog::where('transfer_type', 0)
            ->where('user_id', $freshUser->id)
            ->where('state', 1)
            ->orderBy('id', 'desc')
            ->value('api_type');

        $lastPlatform = strtoupper(trim((string) $lastPlatform));
        if ($lastPlatform !== '' && $lastPlatform !== $platform) {
            $result = $tg->balance($lastPlatform, $freshUser->username);
            if (($result['code'] ?? 201) != 200) {
                return ['code' => 201, 'message' => $result['message'] ?? 'balance query failed'];
            }

            $amount = (int) floor((float) ($result['data'] ?? 0));
            if ($amount >= 1) {
                $moveOut = $this->safeGameToAccountTransfer($freshUser, $lastPlatform, $amount, $tg, 'auto_switch');
                if (($moveOut['code'] ?? 201) != 200) {
                    return $moveOut;
                }
            }
        }

        $freshUser = User::where('id', $freshUser->id)->first();
        if (!$freshUser) {
            return ['code' => 201, 'message' => 'login expired'];
        }

        $amount = (int) floor((float) $freshUser->balance);
        if ($amount >= 1) {
            return $this->safeAccountToGameTransfer($freshUser, $platform, $amount, $tg, 'auto_login');
        }

        return ['code' => 200, 'message' => '成功', 'balance' => $freshUser->balance];
    }

    public function moveLastPlatformBalanceToAccount(User $user, TgService $tg, $source = 'auto_recovery')
    {
        $freshUser = User::where('id', $user->id)->first();
        if (!$freshUser) {
            return ['code' => 201, 'message' => 'login expired'];
        }

        $lastPlatform = TransferLog::where('transfer_type', 0)
            ->where('user_id', $freshUser->id)
            ->where('state', 1)
            ->orderBy('id', 'desc')
            ->value('api_type');

        $lastPlatform = strtoupper(trim((string) $lastPlatform));
        if ($lastPlatform === '') {
            return ['code' => 200, 'message' => 'success', 'balance' => $freshUser->balance];
        }

        $result = $tg->balance($lastPlatform, $freshUser->username);
        if (($result['code'] ?? 201) != 200) {
            return ['code' => 201, 'message' => $result['message'] ?? 'balance query failed'];
        }

        $amount = (int) floor((float) ($result['data'] ?? 0));
        if ($amount < 1) {
            return ['code' => 200, 'message' => 'success', 'balance' => $freshUser->balance];
        }

        return $this->safeGameToAccountTransfer($freshUser, $lastPlatform, $amount, $tg, $source);
    }

    public function moveAccountBalanceToPlatform(User $user, $platform, $amount, TgService $tg, $source = 'manual_transfer')
    {
        $platform = strtoupper(trim((string) $platform));
        $amount = (int) floor((float) $amount);

        if ($platform === '') {
            return ['code' => 201, 'message' => 'game platform is required'];
        }
        if ($amount < 1) {
            return ['code' => 209, 'message' => 'invalid transfer amount'];
        }

        $freshUser = User::where('id', $user->id)->first();
        if (!$freshUser) {
            return ['code' => 201, 'message' => 'login expired'];
        }

        return $this->safeAccountToGameTransfer($freshUser, $platform, $amount, $tg, $source);
    }

    public function movePlatformBalanceToAccount(User $user, $platform, $amount, TgService $tg, $source = 'manual_transfer')
    {
        $platform = strtoupper(trim((string) $platform));
        $amount = (int) floor(abs((float) $amount));

        if ($platform === '') {
            return ['code' => 201, 'message' => 'game platform is required'];
        }
        if ($amount < 1) {
            return ['code' => 209, 'message' => 'invalid transfer amount'];
        }

        $freshUser = User::where('id', $user->id)->first();
        if (!$freshUser) {
            return ['code' => 201, 'message' => 'login expired'];
        }

        return $this->safeGameToAccountTransfer($freshUser, $platform, $amount, $tg, $source);
    }

    protected function safeAccountToGameTransfer(User $user, $platform, $amount, TgService $tg, $source)
    {
        $reserved = DB::transaction(function () use ($user, $platform, $amount, $source) {
            $active = $this->activePendingTransfer($user->id, $platform, 0);
            if ($active) {
                return ['code' => 209, 'message' => 'transfer is processing'];
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return ['code' => 201, 'message' => 'login expired'];
            }
            if ($amount > (float) $lockedUser->balance) {
                return ['code' => 210, 'message' => 'insufficient balance'];
            }

            $before = (float) $lockedUser->balance;
            $lockedUser->balance = $before - $amount;
            $lockedUser->save();

            $log = TransferLog::create([
                'order_no' => $this->makeTransferOrderNo($lockedUser->id),
                'api_type' => $platform,
                'user_id' => $lockedUser->id,
                'transfer_type' => 0,
                'money' => $amount,
                'cash_fee' => 0,
                'real_money' => $amount,
                'before_money' => $before,
                'after_money' => $lockedUser->balance,
                'state' => 0,
                'external_status' => 'pending',
                'recovery_status' => 'calling',
                'reconcile_note' => 'user '.$source.' reserved before upstream deposit',
            ]);

            return ['code' => 200, 'log_id' => $log->id, 'order_no' => $log->order_no];
        });

        if (($reserved['code'] ?? 201) != 200) {
            return $reserved;
        }

        TransferLog::where('id', $reserved['log_id'])->update(['external_status' => 'calling']);
        $res = $tg->deposit($user->username, $amount, $reserved['order_no'], $platform);
        if (($res['code'] ?? 201) != 200) {
            $balance = $this->refundReservedAccountToGameTransfer($reserved['log_id'], $amount, $res);
            return ['code' => 209, 'message' => $res['message'] ?? 'transfer failed', 'balance' => $balance];
        }

        try {
            $balance = $this->completeReservedAccountToGameTransfer($reserved['log_id'], $platform, $amount);
        } catch (\Throwable $e) {
            $this->markTransferLocalPending($reserved['log_id'], $e);
            return ['code' => 500, 'message' => 'local transfer posting failed'];
        }

        return ['code' => 200, 'message' => '成功', 'balance' => $balance];
    }

    protected function safeGameToAccountTransfer(User $user, $platform, $amount, TgService $tg, $source)
    {
        $pending = DB::transaction(function () use ($user, $platform, $amount, $source) {
            $active = $this->activePendingTransfer($user->id, $platform, 1);
            if ($active) {
                return ['code' => 209, 'message' => 'transfer is processing'];
            }

            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return ['code' => 201, 'message' => 'login expired'];
            }

            $log = TransferLog::create([
                'order_no' => $this->makeTransferOrderNo($lockedUser->id),
                'api_type' => $platform,
                'user_id' => $lockedUser->id,
                'transfer_type' => 1,
                'money' => $amount,
                'cash_fee' => 0,
                'real_money' => $amount,
                'before_money' => $lockedUser->balance,
                'after_money' => $lockedUser->balance,
                'state' => 0,
                'external_status' => 'pending',
                'recovery_status' => 'calling',
                'reconcile_note' => 'user '.$source.' waiting for upstream withdrawal',
            ]);

            return ['code' => 200, 'log_id' => $log->id, 'order_no' => $log->order_no];
        });

        if (($pending['code'] ?? 201) != 200) {
            return $pending;
        }

        TransferLog::where('id', $pending['log_id'])->update(['external_status' => 'calling']);
        $res = $tg->withdrawal($user->username, $amount, $pending['order_no'], $platform);
        if (($res['code'] ?? 201) != 200) {
            $this->markTransferFailed($pending['log_id'], $res);
            return ['code' => 209, 'message' => $res['message'] ?? 'transfer failed'];
        }

        try {
            $balance = $this->postGameToAccountTransfer($pending['log_id'], $platform, $amount);
        } catch (\Throwable $e) {
            $this->markTransferLocalPending($pending['log_id'], $e);
            return ['code' => 500, 'message' => 'local transfer posting failed'];
        }

        return ['code' => 200, 'message' => '成功', 'balance' => $balance];
    }

    protected function activePendingTransfer($userId, $platform, $transferType)
    {
        return TransferLog::where('user_id', $userId)
            ->where('api_type', $platform)
            ->where('transfer_type', $transferType)
            ->where('state', 0)
            ->whereIn('external_status', ['pending', 'calling'])
            ->lockForUpdate()
            ->first();
    }

    protected function refundReservedAccountToGameTransfer($logId, $amount, array $res)
    {
        return DB::transaction(function () use ($logId, $amount, $res) {
            $log = TransferLog::where('id', $logId)->lockForUpdate()->first();
            if (!$log) {
                return 0;
            }

            $user = User::where('id', $log->user_id)->lockForUpdate()->first();
            if ($user && (int) $log->state === 0 && in_array($log->external_status, ['pending', 'calling'], true)) {
                $user->balance += $amount;
                $user->save();
                $log->after_money = $user->balance;
            }

            $log->external_status = 'failed';
            $log->recovery_status = 'external_failed';
            $log->reconcile_note = $res['message'] ?? 'upstream deposit failed; local reservation refunded';
            $log->save();

            return $user ? $user->balance : 0;
        });
    }

    protected function completeReservedAccountToGameTransfer($logId, $platform, $amount)
    {
        return DB::transaction(function () use ($logId, $platform, $amount) {
            $log = TransferLog::where('id', $logId)->lockForUpdate()->first();
            if (!$log) {
                return 0;
            }

            $log->state = 1;
            $log->external_status = 'success';
            $log->recovery_status = 'success';
            $log->posted_at = now();
            $log->reconcile_note = null;
            $log->save();

            $this->adjustUserApiBalance($log->user_id, $platform, $amount, 'increase');

            $user = User::where('id', $log->user_id)->first();
            return $user ? $user->balance : $log->after_money;
        });
    }

    protected function postGameToAccountTransfer($logId, $platform, $amount)
    {
        return DB::transaction(function () use ($logId, $platform, $amount) {
            $log = TransferLog::where('id', $logId)->lockForUpdate()->first();
            if (!$log) {
                return 0;
            }

            $user = User::where('id', $log->user_id)->lockForUpdate()->first();
            if (!$user) {
                return 0;
            }

            if ((int) $log->state !== 1) {
                $before = (float) $user->balance;
                $user->balance = $before + $amount;
                $user->save();

                $log->before_money = $before;
                $log->after_money = $user->balance;
                $log->state = 1;
                $log->external_status = 'success';
                $log->recovery_status = 'success';
                $log->posted_at = now();
                $log->reconcile_note = null;
                $log->save();

                $this->adjustUserApiBalance($log->user_id, $platform, $amount, 'decrease');
            }

            return $user->balance;
        });
    }

    protected function markTransferFailed($logId, array $res)
    {
        TransferLog::where('id', $logId)->update([
            'external_status' => 'failed',
            'recovery_status' => 'external_failed',
            'reconcile_note' => $res['message'] ?? 'upstream transfer failed',
        ]);
    }

    protected function markTransferLocalPending($logId, \Throwable $e)
    {
        TransferLog::where('id', $logId)->update([
            'external_status' => 'success',
            'recovery_status' => 'external_success_local_pending',
            'reconcile_note' => 'upstream success but local posting failed: '.$e->getMessage(),
        ]);
    }

    protected function adjustUserApiBalance($userId, $platform, $amount, $direction)
    {
        $userApi = User_Api::where('api_code', $platform)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (!$userApi) {
            $user = User::where('id', $userId)->first();
            $userApi = User_Api::create([
                'user_id' => $userId,
                'api_user' => $user ? $user->username : '',
                'api_pass' => 123456,
                'api_code' => $platform,
                'api_money' => 0,
            ]);
        }

        if ($direction === 'increase') {
            $userApi->api_money += $amount;
        } else {
            $userApi->api_money = max(0, $userApi->api_money - $amount);
        }

        $userApi->save();
    }

    protected function makeTransferOrderNo($userId)
    {
        return date('YmdHis').$userId.rand(100000, 999999);
    }
}
