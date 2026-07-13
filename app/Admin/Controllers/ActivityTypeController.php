<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ActivityType;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;
use Illuminate\Support\Facades\Schema;

class ActivityTypeController extends AdminController
{
    protected $status = [1 => '启用', 0 => '停用'];

    protected function grid()
    {
        return Grid::make(new ActivityType(), function (Grid $grid) {
            $grid->model()->orderBy('sort_order', 'desc')->orderBy('id', 'asc');
            $grid->column('id', 'ID')->sortable();
            $grid->column('icon', '图标')->image('', 50, 50);
            $grid->column('name', '后台中文分类');
            $grid->column('sort_order', '排序')->sortable();
            $grid->column('state', '状态')->using($this->status);
            $grid->column('created_at', '创建时间');
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id', 'ID');
                $filter->equal('state', '状态')->select($this->status);
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new ActivityType(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '后台中文分类');
            if (Schema::hasColumn('activity_types', 'enname')) {
                $show->field('enname', '前台泰文分类');
            }
            $show->field('icon', '图标')->image();
            $show->field('sort_order', '排序');
            $show->field('state', '状态');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    protected function form()
    {
        return Form::make(new ActivityType(), function (Form $form) {
            $form->display('id', 'ID');
            $form->text('name', '后台中文分类')->required();
            if (Schema::hasColumn('activity_types', 'enname')) {
                $form->text('enname', '前台泰文分类');
            }
            $form->image('icon', '图标')->uniqueName();
            $form->number('sort_order', '排序')->default(0);
            $form->radio('state', '状态')->options($this->status)->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            $form->saving(function (Form $form) {
                $contentFields = Schema::hasColumn('activity_types', 'enname')
                    ? ['name', 'enname', 'icon', 'sort_order']
                    : ['name', 'icon', 'sort_order'];

                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $contentFields)) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['state'])) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_PUBLISH_SWITCH);
                }

                $fields = [
                    'name' => '后台中文分类',
                    'icon' => '图标',
                    'sort_order' => '排序',
                    'state' => '状态',
                ];
                if (Schema::hasColumn('activity_types', 'enname')) {
                    $fields['enname'] = '前台泰文分类';
                }

                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('activity.type.create', $form, $fields, [], 'name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('activity.type.update', $form, $fields, [], 'name');
            });
        });
    }
}
