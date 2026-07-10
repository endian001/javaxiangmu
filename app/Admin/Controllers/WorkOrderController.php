<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\WorkOrder;
use App\Models\WorkOrderReply;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Admin;
use Dcat\Admin\Http\Controllers\AdminController;

class WorkOrderController extends AdminController
{
    protected $statusMap = [
        'pending' => '待处理',
        'open' => '处理中',
        'answered' => '已回复',
        'closed' => '已关闭',
    ];

    protected $priorityMap = [
        'low' => '低',
        'normal' => '普通',
        'high' => '高',
        'urgent' => '紧急',
    ];

    protected function grid()
    {
        return Grid::make(new WorkOrder(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('order_no', '工单号')->copyable();
            $grid->column('username', '用户');
            $grid->column('title', '标题')->limit(30);
            $grid->column('category', '类型');
            $grid->column('priority', '优先级')->using($this->priorityMap);
            $grid->column('status', '状态')->using($this->statusMap)->label([
                'pending' => 'warning',
                'open' => 'primary',
                'answered' => 'success',
                'closed' => 'default',
            ]);
            $grid->column('created_at', '创建时间')->sortable();
            $grid->column('updated_at', '更新时间')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('order_no', '工单号');
                $filter->like('username', '用户名');
                $filter->like('title', '标题');
                $filter->equal('status', '状态')->select($this->statusMap);
                $filter->equal('priority', '优先级')->select($this->priorityMap);
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new WorkOrder(), function (Show $show) {
            $show->field('id');
            $show->field('order_no', '工单号');
            $show->field('username', '用户');
            $show->field('title', '标题');
            $show->field('content', '内容')->unescape();
            $show->field('category', '类型');
            $show->field('priority', '优先级')->using($this->priorityMap);
            $show->field('status', '状态')->using($this->statusMap);
            $show->field('admin_reply', '管理员回复')->unescape();
            $show->field('admin_reply_time', '回复时间');
            $show->field('closed_at', '关闭时间');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        return Form::make(new WorkOrder(), function (Form $form) {
            $form->display('id');
            $form->display('order_no', '工单号');
            $form->display('username', '用户');
            $form->text('title', '标题')->required();
            $form->textarea('content', '用户内容')->rows(4)->required();
            $form->text('category', '类型')->default('general');
            $form->select('priority', '优先级')->options($this->priorityMap)->default('normal');
            $form->select('status', '状态')->options($this->statusMap)->default('pending');
            $form->textarea('admin_reply', '管理员回复')->rows(5);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            $form->saving(function (Form $form) {
                if ($form->isEditing()) {
                    $reply = trim((string) $form->admin_reply);
                    if ($reply !== '') {
                        $form->admin_reply_time = date('Y-m-d H:i:s');
                        $form->admin_id = Admin::user() ? Admin::user()->id : null;
                        if ($form->status !== 'closed') {
                            $form->status = 'answered';
                        }
                    }

                    if ($form->status === 'closed' && empty($form->model()->closed_at)) {
                        $form->closed_at = date('Y-m-d H:i:s');
                    }

                    if ($form->status !== 'closed') {
                        $form->closed_at = null;
                    }
                }
            });

            $form->saved(function (Form $form) {
                $reply = trim((string) $form->admin_reply);
                if (!$form->isEditing() || $reply === '') {
                    return;
                }

                $exists = WorkOrderReply::where('work_order_id', $form->getKey())
                    ->where('type', 'admin')
                    ->where('content', $reply)
                    ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 5))
                    ->exists();

                if (!$exists) {
                    WorkOrderReply::create([
                        'work_order_id' => $form->getKey(),
                        'admin_id' => Admin::user() ? Admin::user()->id : null,
                        'content' => $reply,
                        'type' => 'admin',
                    ]);
                }
            });
        });
    }
}
