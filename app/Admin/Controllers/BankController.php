<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Bank;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BankController extends AdminController
{
    protected $state = [1 => '可用',0 => '禁用'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Bank(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('code');
            $grid->column('bank_name');
            $grid->column('order');
            $grid->column('max_amount');
            $grid->column('bank_img')->image('',100,100);
            $grid->column('state')->using($this->state);
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
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
        return Show::make($id, new Bank(), function (Show $show) {
            $show->field('id');
            $show->field('code');
            $show->field('bank_name');
            $show->field('order');
            $show->field('max_amount');
            $show->field('bank_img');
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
        return Form::make(new Bank(), function (Form $form) {
            $form->display('id');
            $form->text('code');
            $form->text('bank_name')->required();
            $form->number('order');
            $form->text('max_amount')->default(0)->required();
            $form->image('bank_img')->uniqueName();
            $form->radio('state')->options($this->state)->default(1);
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
