<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\PaySetting;
use App\Models\Bank;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class PaySettingController extends AdminController
{
    protected $state = [1 => '可用',0 => '禁用'];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PaySetting(with(['bank_data'])), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('bank_data.bank_name','银行');
            $grid->column('bank_no');
            $grid->column('bank_owner');
            $grid->column('bank_address');
            $grid->column('info');
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
        return Show::make($id, new PaySetting(), function (Show $show) {
            $show->field('id');
            $show->field('bank_id');
            $show->field('bank_no');
            $show->field('bank_owner');
            $show->field('bank_address');
            $show->field('info');
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
        
        return Form::make(new PaySetting(), function (Form $form) {
            $bank = Bank::orderBy('order','desc')->get();
            $list = [];
            foreach ($bank as $v) {
                $list[$v->id] = $v->bank_name;
            }
            $form->display('id');
            $form->select('bank_id','银行')->options($list)->required();
            $form->text('bank_no')->required();
            $form->text('bank_owner')->required();
            $form->text('bank_address')->required();
            $form->text('info');
            $form->radio('state')->options($this->state)->default(1);
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
