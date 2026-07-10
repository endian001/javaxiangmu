<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\Syslog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SyslogController extends AdminController
{
    use ReadOnlyResource;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Syslog::with(['user_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('user_data.username','用户名');
            $grid->column('type')->using([1 => '用户登录',2=> '代理登录',3 => '管理员登录']);
            $grid->column('memo','描述');
            $grid->column('memo','IP地址');
            $grid->column('addtime','登录时间');
            $grid->disableActions();
            $grid->disableCreateButton();
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
        return Show::make($id, new Syslog(), function (Show $show) {
            $show->field('id');
            $show->field('uid');
            $show->field('type');
            $show->field('memo');
            $show->field('addtime');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Syslog(), function (Form $form) {
            $form->display('id');
            $form->text('uid');
            $form->text('type');
            $form->text('memo');
            $form->text('addtime');
        });
    }
}
