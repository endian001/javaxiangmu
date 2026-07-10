<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\RedEnvelopes;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class RedEnvelopesController extends AdminController
{

    public $title = '红包管理';

    protected $state = [1 => '启用',0 => '禁用'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {

        return Grid::make(new RedEnvelopes(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('day_flow','充值金额起始');
            $grid->column('flow_money','充值金额结束');
            $grid->column('money','领取红包比例');
            $grid->column('recharge','红包个数');
            $grid->column('start_time','红包领取开始时间');
            $grid->column('end_time','红包领取结束时间');
            $grid->column('status')->using($this->state);
            $grid->column('created_at');

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
        return Show::make($id, new RedEnvelopes(), function (Show $show) {
            $show->field('id');
            $show->field('day_flow','充值金额起始');
            $show->field('flow_money','充值金额结束');
            $show->field('money','领取红包比例');
            $show->field('recharge','领取红包个数');
            $show->field('recharge','状态');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new RedEnvelopes(), function (Form $form) {
            $form->display('id');
            $form->currency('day_flow','充值金额起始')->symbol('￥');
            $form->currency('flow_money','充值金额结束')->symbol('￥');
            $form->currency('money','领取红包比例')->symbol('%');
            $form->text('recharge','领取红包个数');

            $form->radio('status','状态')->options([1 => '可用',0 => '禁用'])->default(1);
            $form->datetimeRange('start_time', 'end_time', '活动时间');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
