<?php

namespace App\Admin\Actions\Grid\User;


use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use App\Models\SystemConfig;
use App\Models\Users;
use App\Models\TransferLog;
use App\Services\Lib;
use App\Services\GamereportService;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Recharge;
use App\Models\RedEnvelopes;
use App\Models\Userredpacket;
use App\Models\UserOperateLog;
use App\User;
use Dcat\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Models\AgentSettlement;

class Fanyong extends RowAction
{
    /**
     * @return string
     */
	protected $title = '立即返佣';

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
        $money = 0;
        $val = User::where('isagent','=',1)->where('id','=',$id)->first();
        if ($val){
            $settlement = AgentSettlement::where('id', $val->settlement_id)->first();
            
            // 计算当月新增会员数量
            $currentMonthStart = date('Y-m-01 00:00:00');
            $currentMonthEnd = date('Y-m-t 23:59:59');
            $newMembersCount = User::UserSum($val->id, $currentMonthStart, $currentMonthEnd);
            
            // 检查是否达到当月新增会员数量要求
            if ($settlement && $settlement->required_new_members > 0 && $newMembersCount < $settlement->required_new_members) {
                return $this->response()->error('当月新增会员数量未达到要求，无法领取返佣')->refresh();
            }
            
            try {
                $money = DB::transaction(function () use ($val, $request) {
                    $user = User::where('id', $val->id)->lockForUpdate()->first();
                    if (! $user) {
                        throw new \RuntimeException('代理用户不存在');
                    }

                    $logs = TransferLog::where('state', 2)
                        ->where('user_id', $user->id)
                        ->where('transfer_type', 20)
                        ->lockForUpdate()
                        ->get();

                    $money = (float) $logs->sum('money');
                    $beforeBalance = (float) $user->balance;
                    if ($money > 0) {
                        $user->balance = $beforeBalance + $money;
                        TransferLog::whereIn('id', $logs->pluck('id')->all())->update(['state' => 1]);
                    }

                    $user->settlementday = strtotime(date('Y-m-d'));
                    $user->save();

                    $auditInfo = json_encode([
                        'action' => 'agent_commission_pass',
                        'admin' => $this->adminName(),
                        'agent_id' => $user->id,
                        'amount' => $money,
                        'before_balance' => $beforeBalance,
                        'after_balance' => (float) $user->balance,
                        'log_ids' => $logs->pluck('id')->all(),
                    ], JSON_UNESCAPED_UNICODE);

                    UserOperateLog::insertLog(
                        $user->id,
                        7,
                        (string) $request->userAgent(),
                        (string) $request->ip(),
                        '',
                        '管理员结算代理【' . $user->username . '】返佣，金额'.$money.'，调整前金额'.$beforeBalance.'，调整后金额'.$user->balance,
                        $auditInfo === false ? '' : $auditInfo
                    );

                    OpsChangeAudit::writeAdminAudit(
                        'agent.commission.settle',
                        'agent_commission',
                        'Settle commission for '.$user->username,
                        [
                            'agent_id' => $user->id,
                            'username' => $user->username,
                            'amount' => $money,
                            'before_balance' => $beforeBalance,
                            'after_balance' => (float) $user->balance,
                            'log_ids' => $logs->pluck('id')->all(),
                        ],
                        $request
                    );

                    return $money;
                });
            } catch (\Throwable $e) {
                return $this->response()->error($e->getMessage())->refresh();
            }
        }        


        return $this->response()->success('成功领取返佣'.$money)->refresh();
    }


    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
		return ['确定立即返佣', ''];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return OperationPermission::can(OperationPermission::AGENT_COMMISSION_SETTLE, $user);
    }

    protected function adminName()
    {
        $admin = Admin::user();
        if (!$admin) {
            return 'unknown';
        }

        if (!empty($admin->username)) {
            return $admin->username;
        }

        if (!empty($admin->name)) {
            return $admin->name;
        }

        return (string) $admin->getKey();
    }
}
