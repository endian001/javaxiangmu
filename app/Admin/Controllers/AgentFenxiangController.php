<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AgentFenxiang;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AgentFenxiangController extends AdminController
{
    protected $state = [1 => '启用', 0 => '禁用'];
    
    protected $oneTimeOptions = [1 => '只能领取一次', 0 => '可重复领取'];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AgentFenxiang(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('invite_count', '邀请下级代理数量');
            $grid->column('reward_amount', '对应奖励');
            $grid->column('one_time', '领取限制')->using($this->oneTimeOptions);
            $grid->column('sort_order', '排序')->sortable();
            $grid->column('state', '状态')->using($this->state);
            $grid->column('created_at', '创建时间');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('one_time', '领取限制')->select($this->oneTimeOptions);
            });

            $grid->model()->orderBy('sort_order', 'asc');
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
        return Show::make($id, new AgentFenxiang(), function (Show $show) {
            $show->field('id');
            $show->field('invite_count', '邀请下级代理数量');
            $show->field('reward_amount', '对应奖励');
            $show->field('one_time', '领取限制')->using($this->oneTimeOptions);
            $show->field('sort_order', '排序');
            $show->field('state', '状态');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AgentFenxiang(), function (Form $form) {
            $form->display('id');
            $form->number('invite_count', '邀请下级代理数量')->required()->help('设置指定下级代理数量');
            $form->decimal('min_recharge_amount', '最低充值金额')->default(0.00)->help('设置下级代理必须满足的最低充值金额');
            $form->decimal('reward_amount', '对应奖励')->default(0.00)->required()->help('设置可以领取的对应奖励金额');
            $form->radio('one_time', '领取限制')->options($this->oneTimeOptions)->default(1)->help('只能领取一次不能重复领取');
            $form->number('sort_order', '排序')->default(0)->help('数字越小越靠前');
            $form->radio('state', '状态')->options([1 => '启用', 0 => '禁用'])->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
