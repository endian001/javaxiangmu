<?php

namespace App\Admin\Actions\Grid\Activity;

use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use App\Models\ActivityApply;
use App\Models\Activity;
use App\Models\TransferLog;
use App\Models\Users;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Pass extends RowAction
{
    /**
     * @return string
     */
	protected $title = '通过';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $id = $this->getKey();
        OperationPermission::assert(OperationPermission::ACTIVITY_APPLY_AUDIT);
        try {
            $result = DB::transaction(function () use ($id) {
                $item = ActivityApply::where('id', $id)->lockForUpdate()->first();
                if (! $item) {
                    return ['error' => '申请记录不存在'];
                }
                if ((int) $item->state !== 1) {
                    return ['error' => '申请记录已处理'];
                }

                $item->state = 2;
                $checkedAt = date('Y-m-d H:i:s');
                $item->check_time = $checkedAt;
                $item->save();

                $activity = Activity::where('id', $item->activity_id)->lockForUpdate()->first();
                if ($activity) {
                    $activity->apply_count++;
                    $activity->save();
                }

                $rewardAmount = $this->activityRewardAmount($item);
                $transferLog = null;
                if ($rewardAmount > 0) {
                    if (!Schema::hasColumn('activity_apply', 'issued_transfer_log_id')) {
                        throw new \RuntimeException('活动发放字段未迁移');
                    }
                    if (!empty($item->issued_transfer_log_id)) {
                        throw new \RuntimeException('活动奖励已经发放');
                    }

                    $user = Users::where('id', $item->user_id)->lockForUpdate()->first();
                    if (!$user) {
                        throw new \RuntimeException('会员不存在');
                    }

                    $beforeBalance = $user->balance;
                    $user->balance = $beforeBalance + $rewardAmount;
                    $user->save();

                    $transferLog = TransferLog::create([
                        'order_no' => $this->makeActivityRewardOrderNo($item->id, $user->id),
                        'api_type' => 'web',
                        'user_id' => $user->id,
                        'transfer_type' => 5,
                        'money' => $rewardAmount,
                        'cash_fee' => 0,
                        'real_money' => $rewardAmount,
                        'before_money' => $beforeBalance,
                        'after_money' => $user->balance,
                        'state' => 1,
                        'remark' => 'activity reward '.$item->activity_id,
                    ]);

                    $item->issued_transfer_log_id = $transferLog->id;
                    if (Schema::hasColumn('activity_apply', 'issued_at')) {
                        $item->issued_at = $checkedAt;
                    }
                    $item->save();
                }

                return [
                    'ok' => true,
                    'target_id' => $item->id,
                    'target_name' => 'activity '.$item->activity_id.' user '.$item->user_id,
                    'changes' => [
                        'state' => ['label' => 'audit state', 'old' => 'pending', 'new' => 'approved'],
                        'check_time' => ['label' => 'check time', 'old' => '', 'new' => $checkedAt],
                        'activity_id' => ['label' => 'activity id', 'old' => '', 'new' => $item->activity_id],
                        'user_id' => ['label' => 'user id', 'old' => '', 'new' => $item->user_id],
                        'reward_amount' => ['label' => 'reward amount', 'old' => 0, 'new' => $rewardAmount],
                        'transfer_log_id' => ['label' => 'transfer log id', 'old' => '', 'new' => $transferLog ? $transferLog->id : ''],
                    ],
                ];
            });
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage())->refresh();
        }

        if (!empty($result['error'])) {
            return $this->response()->error($result['error'])->refresh();
        }
        OpsChangeAudit::insert('activity.apply.pass', $result['target_id'] ?? $id, $result['target_name'] ?? '', $result['changes'] ?? []);

        return $this->response()
            ->success('审核成功')
            ->refresh();
    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		// return ['Confirm?', 'contents'];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return OperationPermission::can(OperationPermission::ACTIVITY_APPLY_AUDIT, $user);
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }

    protected function activityRewardAmount($item)
    {
        if (!$item || !isset($item->reward_amount)) {
            return 0;
        }

        return max(0, (float) $item->reward_amount);
    }

    protected function makeActivityRewardOrderNo($applyId, $userId)
    {
        return date('YmdHis').'_activity_'.$applyId.'_'.$userId.'_'.mt_rand(100000, 999999);
    }
}
