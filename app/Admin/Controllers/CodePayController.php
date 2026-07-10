<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\CodePay;
use App\Models\PayType;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class CodePayController extends AdminController
{
    protected $state = [1 => '可用', 0 => '禁用'];
    
    protected $categories = [
        'hot' => '热门',
        'wallet' => '钱包',
        'alipay' => '支付宝',
        'wechat' => '微信',
        'other' => '其他'
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new CodePay(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('payType.icon', '支付类型')->image('', 40, 40);
            $grid->column('payType.name', '支付类型名称');
            $grid->column('category', '支付分类')->using($this->categories);
            $grid->column('content', '标题');
            $grid->column('min_price', '最低充值金额');
            $grid->column('max_price', '最大充值金额');
            $grid->column('remark', '支付备注')->limit(30);
            $grid->column('status', '状态')->using($this->state);
            $grid->column('created_at', '创建时间');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('pay_type_id', '支付类型')->select(PayType::where('state', 1)->pluck('name', 'id'));
                $filter->equal('category', '支付分类')->select($this->categories);
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
        return Show::make($id, new CodePay(), function (Show $show) {
            $show->field('id');
            $show->field('payType.name', '支付类型名称');
            $show->field('payType.icon', '支付类型图标')->image();
            $show->field('category', '支付分类')->using($this->categories);
            $show->field('content', '标题');
            $show->field('min_price', '最低充值金额');
            $show->field('max_price', '最大充值金额');
            $show->field('payimg', '支付图标')->image();
            $show->field('remark', '支付备注');
            $show->field('status', '状态');
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
        return Form::make(new CodePay(), function (Form $form) {
            $form->display('id');
            $form->select('category', '支付分类')
                ->options($this->categories)
                ->required();
            $form->select('pay_type_id', '支付类型')
                ->options(PayType::where('state', 1)->pluck('name', 'id'))
                ->required();
            $form->text('content', '标题')->required();
            $form->decimal('min_price', '最低充值金额')->required();
            $form->decimal('max_price', '最大充值金额')->required();
            $form->image('payimg', '支付图标')->uniqueName()->required();
            $form->textarea('remark', '支付备注')->rows(3)->help('支付备注信息，显示在前端支付页面');
            $form->text('download_name', '下载名称')->help('填写后将在前端显示下载按钮名称，例如：APP下载');
            $form->url('download_url', '下载地址')->help('填写APP下载链接地址');
            $form->radio('status', '状态')->options([1 => '可用', 0 => '禁用'])->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
