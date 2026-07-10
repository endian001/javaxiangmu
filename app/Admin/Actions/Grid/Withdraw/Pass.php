<?php

namespace App\Admin\Actions\Grid\Withdraw;

use App\Admin\Support\OperationPermission;
use App\Models\UserOperateLog;
use App\Models\Withdraw;
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
                $model = Withdraw::where('id', $id)->lockForUpdate()->first();
                if (!$model) {
                    return ['error' => 'Withdraw order not found'];
                }
                if ((int) $model->state !== 1) {
                    return ['error' => 'Withdraw order already handled'];
                }

                $user = User::where('id', $model->user_id)->lockForUpdate()->first();
                if (!$user) {
                    return ['error' => 'User not found'];
                }

                $model->state = 2;
                $model->save();

                UserOperateLog::insertLog(
                    $user->id,
                    7,
                    $ua,
                    $ip,
                    '',
                    $this->shortText('admin '.$this->adminName().' approved withdraw id='.$model->id.' amount='.$model->amount.' balance='.$user->balance),
                    $this->auditInfo([
                        'action' => 'withdraw_pass',
                        'admin' => $this->adminName(),
                        'withdraw_id' => $model->id,
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
        return OperationPermission::can(OperationPermission::FINANCE_WITHDRAW_PASS, $user);
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
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
