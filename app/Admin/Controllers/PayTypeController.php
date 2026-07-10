<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\PayType;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class PayTypeController extends AdminController
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
        return Grid::make(new PayType(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('icon', '图标')->image('', 50, 50);
            $grid->column('name', '支付类型名称');
            $grid->column('merchant_no', '第三方商户号');
            $grid->column('category', '支付分类')->using($this->categories);
            $grid->column('bonus_ratio', '赠送比例(%)');
            $grid->column('sort_order', '排序')->sortable();
            $grid->column('state', '状态')->using($this->state);
            $grid->column('created_at', '创建时间');

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name', '支付类型名称');
                $filter->like('merchant_no', '第三方商户号');
                $filter->equal('category', '支付分类')->select($this->categories);
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
        return Show::make($id, new PayType(), function (Show $show) {
            $show->field('id');
            $show->field('icon', '图标')->image();
            $show->field('name', '支付类型名称');
            $show->field('merchant_no', '第三方商户号');
            $show->field('merchant_key', '第三方密钥');
            $show->field('merchant_url', '第三方支付URL');
            $show->field('merchant_identifier', '第三方支付标识');
            $show->field('merchant_code', '第三方支付代码');
            $show->field('bonus_ratio', '赠送比例(%)');
            $show->field('category', '支付分类')->using($this->categories);
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
        return Form::make(new PayType(), function (Form $form) {
            $form->display('id');
            $form->text('name', '支付类型名称')->required();
            $form->text('merchant_no', '第三方商户号');
            $form->text('merchant_key', '第三方密钥');
            $form->text('merchant_url', '第三方支付URL');
            $form->text('merchant_identifier', '第三方支付标识');
            $form->text('merchant_code', '第三方支付代码');
            $form->decimal('bonus_ratio', '赠送比例(%)')->default(0.00)->help('例如：填写2表示赠送2%');
            $form->select('category', '支付分类')->options($this->categories)->required();
            $form->image('icon', '图标')->uniqueName()->help('建议上传正方形图片，尺寸 100x100 像素');
            $form->number('sort_order', '排序')->default(0)->help('数字越小越靠前');
            $form->radio('state', '状态')->options([1 => '可用', 0 => '禁用'])->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
