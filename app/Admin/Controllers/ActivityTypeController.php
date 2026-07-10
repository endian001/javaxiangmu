<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\ActivityType;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ActivityTypeController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ActivityType(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('icon', '图标')->image('', 50, 50);
            $grid->column('name');
            $grid->column('created_at');
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });
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
        return Show::make($id, new ActivityType(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('state');
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
        return Form::make(new ActivityType(), function (Form $form) {
            $form->display('id');
            $form->text('name', '类型');
            $form->image('icon', '图标')->uniqueName();
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['name', 'icon'])) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('activity.type.create', $form, [
                        'name' => 'activity type name',
                        'icon' => 'icon',
                    ], [], 'name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('activity.type.update', $form, [
                    'name' => 'activity type name',
                    'icon' => 'icon',
                ], [], 'name');
            });
        });
    }
}
