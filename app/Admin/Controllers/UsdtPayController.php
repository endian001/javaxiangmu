<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\UsdtPay;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UsdtPayController extends AdminController
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
        return Grid::make(new UsdtPay(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('category', '支付分类')->using($this->categories);
            $grid->column('wallet_address', 'USDT钱包地址')->limit(30);
            $grid->column('pay_qrcode', '支付二维码')->image('', 50, 50);
            $grid->column('exchange_rate', 'USDT汇率');
            $grid->column('min_price', '最低金额');
            $grid->column('max_price', '最大金额');
            $grid->column('bonus_ratio', '赠送比例(%)');
            $grid->column('pay_icon', '支付图标')->image('', 40, 40);
            $grid->column('status', '状态')->using($this->state);
            $grid->column('sort_order', '排序')->sortable();
            $grid->column('created_at', '创建时间');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('category', '支付分类')->select($this->categories);
                $filter->like('wallet_address', 'USDT钱包地址');
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
        return Show::make($id, new UsdtPay(), function (Show $show) {
            $show->field('id');
            $show->field('category', '支付分类')->using($this->categories);
            $show->field('wallet_address', 'USDT钱包地址');
            $show->field('pay_qrcode', '支付二维码')->image();
            $show->field('exchange_rate', 'USDT汇率');
            $show->field('min_price', '最低金额');
            $show->field('max_price', '最大金额');
            $show->field('bonus_ratio', '赠送比例(%)');
            $show->field('pay_icon', '支付图标')->image();
            $show->field('status', '状态')->using($this->state);
            $show->field('sort_order', '排序');
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
        return Form::make(new UsdtPay(), function (Form $form) {
            $form->display('id');
            $form->select('category', '支付分类')
                ->options($this->categories)
                ->required();
            $form->text('wallet_address', 'USDT钱包地址')->required();
            $form->image('pay_qrcode', '支付二维码')->uniqueName()->required()->help('请上传USDT支付二维码');
            $form->decimal('exchange_rate', 'USDT汇率')->default(1.0000)->required()->help('USDT对人民币的汇率，例如：7.0000');
            $form->decimal('min_price', '最低金额')->default(1.00)->required();
            $form->decimal('max_price', '最大金额')->default(10000.00)->required();
            $form->decimal('bonus_ratio', '赠送比例(%)')->default(0.00)->help('例如：填写2表示赠送2%');
            $form->image('pay_icon', '支付图标')->uniqueName()->required()->help('建议上传正方形图片，尺寸 100x100 像素');
            $form->number('sort_order', '排序')->default(0)->help('数字越小越靠前');
            $form->radio('status', '状态')->options([1 => '可用', 0 => '禁用'])->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
