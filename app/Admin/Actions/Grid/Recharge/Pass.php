<?php

namespace App\Admin\Actions\Grid\Recharge;

use App\Admin\Support\OperationPermission;
use App\Models\Recharge;
use App\Models\SystemConfig;
use App\Models\UserOperateLog;
use App\Models\Users;
use App\Models\UserVip;
use App\Services\PromotionPixelEventService;
use App\User;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Pass extends RowAction
{
    /**
     * @return string
     */
    protected $title = 'Pass';

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
        $ip = $request->ip();
        $ua = $request->userAgent() ?: '';

        try {
            $result = DB::transaction(function () use ($id, $ip, $ua) {
                $model = Recharge::where('id', $id)->lockForUpdate()->first();
                if (!$model) {
                    return ['error' => 'Recharge order not found'];
                }
                if ((int) $model->state !== 1) {
                    return ['error' => 'Recharge order already handled'];
                }

                $user = User::where('id', $model->user_id)->lockForUpdate()->first();
                if (!$user) {
                    return ['error' => 'User not found'];
                }

                $beforeBalance = $user->balance;
                $user->balance += $model->amount;
                $user->paysum += $model->amount;
                $user->save();

                $model->state = 2;
                $model->save();
                (new PromotionPixelEventService())->recordDepositArrival($model, ['source' => 'admin_recharge_pass']);

                $this->sendmoney($user, $model->amount);
                $this->upuserlevel($model->user_id);

                $freshUser = User::find($model->user_id);
                $afterBalance = $freshUser ? $freshUser->balance : $user->balance;

                UserOperateLog::insertLog(
                    $user->id,
                    7,
                    $ua,
                    $ip,
                    '',
                    $this->shortText('admin '.$this->adminName().' approved recharge id='.$model->id.' amount='.$model->amount.' balance='.$beforeBalance.'->'.$afterBalance),
                    $this->auditInfo([
                        'action' => 'recharge_pass',
                        'admin' => $this->adminName(),
                        'recharge_id' => $model->id,
                        'order_no' => $model->order_no,
                        'state_from' => 1,
                        'state_to' => 2,
                    ])
                );

                return ['ok' => true];
            });
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage())->refresh();
        }

        if (!empty($result['error'])) {
            return $this->response()->error($result['error'])->refresh();
        }

        return $this->response()->success('Audit success')->refresh();
    }

    /**
     * Recharge bonus.
     *
     * @return string|array|void
     */
    public function sendmoney($user, $money)
    {
        $recharge_fee = SystemConfig::getValue('recharge_fee');
        if (!$recharge_fee) {
            return;
        }

        $amount = $money * $recharge_fee / 100;
        if ((float) $amount <= 0) {
            return;
        }

        $freshUser = User::where('id', $user->id)->lockForUpdate()->first();
        if (!$freshUser) {
            return;
        }

        $freshUser->balance += $amount;
        $freshUser->save();

        Recharge::create([
            'order_no' => $freshUser->id.time().rand(10000, 90000),
            'out_trade_no' => $freshUser->id.time().rand(10000, 90000),
            'user_id' => $freshUser->id,
            'amount' => $amount,
            'cash_fee' => 0,
            'real_money' => $amount,
            'pay_way' => 10,
            'info' => 'recharge_bonus',
            'state' => 2,
        ]);
    }

    /**
     * @return string|array|void
     */
    public function confirm()
    {
        return ['Confirm approve', ''];
    }

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return OperationPermission::can(OperationPermission::FINANCE_RECHARGE_PASS, $user);
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }

    public function upuserlevel($uid)
    {
        $userinfo = Users::find($uid);
        if (!$userinfo) {
            return;
        }

        $uservip = UserVip::where('status', 1)
            ->where('recharge', '<=', $userinfo->paysum)
            ->where('flow', '<=', $userinfo->totalgame)
            ->orderBy('id', 'desc')
            ->first();

        if (!$uservip) {
            return;
        }

        $userinfo->vip = $uservip->id;
        $userinfo->save();
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

    protected function auditInfo(array $data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $json === false ? '' : $this->shortText($json);
    }

    protected function shortText($text)
    {
        return substr((string) $text, 0, 255);
    }
}
