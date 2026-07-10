<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\UserCard;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserCardController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(UserCard::with(['user_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('user_data.username','用户名')->view('admin.field.user_username');
            $grid->column('bank','类型');
            $grid->column('bank_no','银行卡号/USDT地址');
            //$grid->column('bank_address');
            $grid->column('bank_owner','姓名/协议');
            $grid->column('created_at');
            //$grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('user_data.username','用户名');
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
        return Show::make($id, new UserCard(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('bank');
            $show->field('bank_no');
            $show->field('bank_address');
            $show->field('bank_owner');
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
        return Form::make(new UserCard(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('bank');
            $form->text('bank_no');
            $form->text('bank_address');
            $form->text('bank_owner');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
