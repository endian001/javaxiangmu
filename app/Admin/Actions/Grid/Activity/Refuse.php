<?php

namespace App\Admin\Actions\Grid\Activity;

use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use App\Models\ActivityApply;
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

                $item->state = 3;
                $checkedAt = date('Y-m-d H:i:s');
                $item->check_time = $checkedAt;
                $item->save();

                return [
                    'ok' => true,
                    'target_id' => $item->id,
                    'target_name' => 'activity '.$item->activity_id.' user '.$item->user_id,
                    'changes' => [
                        'state' => ['label' => 'audit state', 'old' => 'pending', 'new' => 'refused'],
                        'check_time' => ['label' => 'check time', 'old' => '', 'new' => $checkedAt],
                        'activity_id' => ['label' => 'activity id', 'old' => '', 'new' => $item->activity_id],
                        'user_id' => ['label' => 'user id', 'old' => '', 'new' => $item->user_id],
                    ],
                ];
            });
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage())->refresh();
        }

        if (!empty($result['error'])) {
            return $this->response()->error($result['error'])->refresh();
        }
        OpsChangeAudit::insert('activity.apply.refuse', $result['target_id'] ?? $id, $result['target_name'] ?? '', $result['changes'] ?? []);

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
}
