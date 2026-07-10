<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ActivityType;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ActivityTypeController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new ActivityType(), function (Grid $grid) {
            $grid->model()->orderBy('sort_order', 'desc')->orderBy('id', 'asc');
            $grid->column('id')->sortable();
            $grid->column('icon', 'Icon')->image('', 50, 50);
            $grid->column('name', 'Thai category');
            $grid->column('sort_order', 'Sort')->sortable();
            $grid->column('state', 'Status')->using([1 => 'Enabled', 0 => 'Disabled']);
            $grid->column('created_at');
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('state', 'Status')->select([1 => 'Enabled', 0 => 'Disabled']);
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new ActivityType(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('icon')->image();
            $show->field('sort_order');
            $show->field('state');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    protected function form()
    {
        return Form::make(new ActivityType(), function (Form $form) {
            $form->display('id');
            $form->text('name', 'Thai category')->required();
            $form->image('icon', 'Icon')->uniqueName();
            $form->number('sort_order', 'Sort')->default(0);
            $form->radio('state', 'Status')->options([1 => 'Enabled', 0 => 'Disabled'])->default(1);
            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) {
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['name', 'icon', 'sort_order'])) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['state'])) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_PUBLISH_SWITCH);
                }

                $fields = [
                    'name' => 'thai category',
                    'icon' => 'icon',
                    'sort_order' => 'sort order',
                    'state' => 'state',
                ];
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('activity.type.create', $form, $fields, [], 'name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('activity.type.update', $form, $fields, [], 'name');
            });
        });
    }
}
