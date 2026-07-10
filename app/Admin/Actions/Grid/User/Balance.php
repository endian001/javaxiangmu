<?php

namespace App\Admin\Actions\Grid\User;
use App\Admin\Support\OperationPermission;
use App\Models\Users;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;
use Dcat\Admin\Widgets\Modal;
use App\Admin\Forms\Userbalance;
class Balance extends RowAction
{
    /**
     * @return string
     */
	protected $title = '调整金额';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function render()
    {
/*        $item = Users::find($id);
        $table = Userbalance::make($item->toArray());*/
        $form = Userbalance::make()->payload(['id' => $this->getKey()]);

        return Modal::make()
            ->lg()
            ->title('调整金额')
            ->body($form)
            ->button('调整金额');

    }

    /**
	 * @return string|array|void
	 */
	public function confirm()
	{
       // return ['你确定要删除此行内容吗？', '弹窗内容'];
	}

    /**
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
    protected function authorize($user): bool
    {
        return OperationPermission::can(OperationPermission::MEMBER_BALANCE_ADJUST, $user);
    }

    /**
     * @return array
     */
    protected function parameters()
    {
        return [];
    }


}
