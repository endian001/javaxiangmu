<?php

namespace App\Admin\Actions\Grid\AgentApply;

use App\Admin\Support\OperationPermission;
use App\Models\AgentApply;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Refuse extends RowAction
{
    /**
     * @return string
     */
	protected $title = '拒绝';

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
        try {
            $result = DB::transaction(function () use ($id) {
                $item = AgentApply::where('id', $id)->lockForUpdate()->first();
                if (! $item) {
                    return ['error' => '申请记录不存在'];
                }
                if ((int) $item->state !== 1) {
                    return ['error' => '申请记录已处理'];
                }

                $item->state = 3;
                $item->save();

                return ['ok' => true];
            });
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage())->refresh();
        }

        if (!empty($result['error'])) {
            return $this->response()->error($result['error'])->refresh();
        }

        return $this->response()
            ->success('审核成功')
            ->redirect(admin_url('agent-applys'));
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
        return OperationPermission::can(OperationPermission::AGENT_APPLY_AUDIT, $user);
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }
}
