<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AgentSettlement;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AgentSettlementController extends AdminController
{

    protected $type = [1 => '返点',2 => '返佣'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AgentSettlement(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('type')->using($this->type);
            $grid->column('member_fs','代理返佣(%)');
            $grid->column('required_new_members','当月新增会员数量要求');
            // $grid->column('state');
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableView();
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
        return Show::make($id, new AgentSettlement(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('type');
  /*          $show->field('realperson');
            $show->field('electron');
            $show->field('joker');
            $show->field('sport');
            $show->field('fish');
            $show->field('lottery');
            $show->field('e_sport');*/
            $show->field('member_fs');
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
        return Form::make(new AgentSettlement(), function (Form $form) {
            $form->display('id');
            $form->text('name')->required();
            $form->hidden('type')->default(2);
/*            $form->number('realperson');
            $form->number('electron');
            $form->number('joker');
            $form->number('sport');
            $form->number('fish');
            $form->number('lottery');
            $form->number('e_sport');*/
            $form->number('member_fs',"返佣(%)");
            $form->number('required_new_members','当月新增会员数量要求')->default(0);
            // $form->text('state');

            $form->display('created_at');
            $form->display('updated_at');

            $form->tools(function (Form\Tools $tools) {
                // 去掉跳转详情页按钮
                $tools->disableView();
                // 去掉删除按钮
                $tools->disableDelete();
            });
        });
    }
}
