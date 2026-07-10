<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\ActivityApply;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Admin\Actions\Grid\Activity;
use Dcat\Admin\Admin;

class ActivityApplyController extends AdminController
{
    use ReadOnlyResource;

    protected $state = [1 => '待审核',2 => '通过',3 => '拒绝'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        //Admin::js('/admin/js/activity_apply.js');
        return Grid::make(ActivityApply::with(['activity_data','user_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('activity_data.title','活动名称');
            $grid->column('user_data.username','申请人');
            $grid->column('state')->using($this->state);
            $grid->column('check_time');
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
            });

            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->disableView();
                if ($actions->row->state == 1) {
                    $actions->append(new Activity\Pass());
                    $actions->append(new Activity\Refuse());
                }
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
        return Show::make($id, new ActivityApply(), function (Show $show) {
            $show->field('id');
            $show->field('activity_id');
            $show->field('user_id');
            $show->field('state');
            $show->field('check_time');
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
        return Form::make(new ActivityApply(), function (Form $form) {
            $form->display('id');
            $form->text('activity_id');
            $form->text('user_id');
            $form->text('state');
            $form->text('check_time');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
