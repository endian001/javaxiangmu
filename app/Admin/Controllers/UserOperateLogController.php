<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\UserOperateLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class UserOperateLogController extends AdminController
{
    use ReadOnlyResource;

    protected $type = [
        1 => 'Login',
        2 => 'Logout',
        3 => 'Member operation',
        4 => 'Agent login',
        5 => 'Agent logout',
        6 => 'Game transfer exception',
        7 => 'Admin operation',
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserOperateLog(['user_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('user_data.username', 'Username');
            $grid->column('type')->using($this->type);
            $grid->column('login_ip');
            $grid->column('ip_address');
            $grid->column('desc');
            $grid->column('created_at');

            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableBatchDelete();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableDelete();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('user_data.username', 'Username');
                $filter->equal('type')->select($this->type);
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
        return Show::make($id, new UserOperateLog(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('type');
            $show->field('login_ua');
            $show->field('login_ip');
            $show->field('ip_address');
            $show->field('desc');
            $show->field('info');
            $show->field('created_at');
            $show->field('updated_at');

            $show->disableEditButton();
            $show->disableDeleteButton();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserOperateLog(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('type');
            $form->text('login_ua');
            $form->text('login_ip');
            $form->text('ip_address');
            $form->text('desc');
            $form->text('info');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
