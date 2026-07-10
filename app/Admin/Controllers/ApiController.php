<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Api;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ApiController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
 
	protected $category = ['1' => '<font color="blue">可用</font>','0' => '<font color="red">禁用</font>'];  
    protected function grid()
    {
        return Grid::make(new Api(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('api_code');
            $grid->column('api_name');
            $grid->column('api_money')->display(function (){
                $code = htmlspecialchars((string) $this->api_code, ENT_QUOTES, 'UTF-8');
                $money = htmlspecialchars((string) $this->api_money, ENT_QUOTES, 'UTF-8');
				$id = 'money_'.$code;
                return '<span id="'.$id.'">'.$money.'</span>&nbsp;&nbsp;&nbsp;<a onclick="test(this)" id="'.$code.'">刷新</a>';
            });			
            $grid->column('state')->using($this->category);
            $grid->column('app_state','APP状态')->using($this->category);
			$grid->column('order_by');
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
				$filter->like('api_code');
                $filter->equal('state')->select($this->category);
                $filter->equal('app_state','APP状态')->select($this->category);
        
            });
            $grid->disableBatchDelete();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Api(), function (Show $show) {
            $show->field('id');
            $show->field('api_code');
            $show->field('api_name');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Api(), function (Form $form) {
            $form->display('id');
            $form->text('api_code')->required();
            $form->text('api_name')->required();
			$form->image('app_icon','接口图标')->uniqueName();
			$form->number('order_by')->default(0)->help("数字越小越靠前");
            $form->radio('state')->options([1 => '可用',0 => '禁用'])->default(1);        
            $form->radio('app_state','APP状态')->options([1 => '可用',0 => '禁用'])->default(1);
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['api_code', 'api_name', 'app_icon', 'order_by'])) {
                    OperationPermission::assert(OperationPermission::API_PLATFORM_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['state', 'app_state'])) {
                    OperationPermission::assert(OperationPermission::API_PLATFORM_SWITCH);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('api.platform.create', $form, [
                        'api_code' => 'api code',
                        'api_name' => 'api name',
                        'app_icon' => 'app icon',
                        'order_by' => 'sort order',
                        'state' => 'state',
                        'app_state' => 'app state',
                    ], [], 'api_name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('api.platform.update', $form, [
                    'api_code' => 'api code',
                    'api_name' => 'api name',
                    'app_icon' => 'app icon',
                    'order_by' => 'sort order',
                    'state' => 'state',
                    'app_state' => 'app state',
                ], [], 'api_name');
            });
        });
    }
}
