<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Message;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Models\UserVip;

class MessageController extends AdminController
{
    protected $type = [1 => '通知',2 => '活动',3 => '公告'];
    
    protected $vip =[];
    
    protected $isagent = [1 => '代理'];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Message(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('type')->using($this->type);
            $grid->column('title');
            //$grid->column('content')->limit(20);
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
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
        return Show::make($id, new Message(), function (Show $show) {
            $show->field('id');
            $show->field('type')->using($this->type);
            $show->field('title');
            $show->field('content');
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
        return Form::make(new Message(), function (Form $form) {
            $form->display('id');
           
            $form->select('type')->options($this->type)->required();
            
             //vip等级
            $settlements = UserVip::all();
            $options = [0 => '所有等级'];
            foreach ($settlements as $k => $v) {
                $options[$v->id] = $v->vipname;
            }
            
            $form->select('vip_id','vip等级')->options($options)->default(0);
            
            $form->select('isagent','发送目标')->options([0 => '所有用户', 1 => '仅代理'])->default(0);
            
            $form->number('user_id','特定用户ID')->default(0)->help('输入0表示发送给所有用户');
            
            $form->text('title')->required();
            $form->editor('content');
        
            
        
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                $fields = ['type', 'vip_id', 'isagent', 'user_id', 'title', 'content'];
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $fields)) {
                    OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('ops.message.create', $form, [
                        'type' => 'message type',
                        'vip_id' => 'vip target',
                        'isagent' => 'agent target',
                        'user_id' => 'user target',
                        'title' => 'title',
                        'content' => 'content',
                    ], [], 'title');
                    return;
                }
                OpsChangeAudit::writeFormChanges('ops.message.update', $form, [
                    'type' => 'message type',
                    'vip_id' => 'vip target',
                    'isagent' => 'agent target',
                    'user_id' => 'user target',
                    'title' => 'title',
                    'content' => 'content',
                ], [], 'title');
            });
        });
    }
}
