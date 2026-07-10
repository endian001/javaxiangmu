<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\Withdraw;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Admin\Actions\Grid\Withdraw\Pass;
use App\Admin\Actions\Grid\Withdraw\Refuse;
use Dcat\Admin\Admin;

class WithdrawController extends AdminController
{
    use ReadOnlyResource;

    protected $title = '提现审核';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        //Admin::js('/admin/js/withdraw_apply.js');
        Admin::script(<<<JS
                $('.copyClick').click(function(){
                    var text = $(this).children('.copyValue');
                    console.log(text);
                    text.unbind();
                    text.select(); // 选中文本
                    document.execCommand("copy"); // 执行浏览器复制命令
                    Dcat.success('复制成功');
                })
JS
            );
        return Grid::make(new Withdraw(with(['card_data','user_data'])), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            //$grid->column('order_no');
            $grid->column('user_data.username','用户名')->view('admin.field.user_username');
            $grid->column('card_data.bank_owner','姓名/协议');
            
            //$grid->column('card_data.bank_no','提款信息');
            
            
            $grid->column('amount','提款金额');
            $grid->column('type','提款方式')->using([0 => '未记录',1 => '银行卡',2 => 'USDT-TRC20',3 => 'USDT-ERC20',4 => 'EBpay']);
            $grid->column('usdt_rate','汇率');
            $grid->column('cash_fee');
            $grid->column('real_money','实际提款');
            
            $grid->column('state')->using([1 => '待审核',2 => '已完成',3 => '已拒绝',4 => '存在错误']);
            $grid->column('created_at');

            $grid->disableCreateButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('user_data.username','用户名');
                $filter->between('created_at', '日期')->date();
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableDelete();
                if ($actions->row->state == 1 || $actions->row->state == 4) {
                    $actions->append(new Pass());
                    $actions->append(new Refuse());
                }
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
        Admin::script(<<<JS
                //支付宝帐号信息复制操作JS
                $('.copyClick').click(function(){
                    var text = $(this).children('.copyValue');
                    console.log(text);
                    text.unbind();
                    text.select(); // 选中文本
                    document.execCommand("copy"); // 执行浏览器复制命令
                    Dcat.success('复制成功');
                })
JS
            );
        return Show::make($id, new Withdraw(with(['card_data','user_data'])), function (Show $show) {
            $show->field('id');
            $show->field('order_no')->view('admin.field.copy_content');
            $show->field('card_id')->view('admin.field.copy_content');
            $show->field('user_data.username','用户名')->view('admin.field.copy_content');
            $show->field('card_data.bank_owner','姓名/协议')->view('admin.field.copy_content');
            $show->field('type','提款方式')->using([0 => '未记录',1 => '银行卡',2 => 'USDT-TRC20']);
            $show->field('card_data.bank_no','卡号/USDT')->view('admin.field.copy_content');
            $show->field('amount')->view('admin.field.copy_content');
            $show->field('cash_fee')->view('admin.field.copy_content');
            $show->field('real_money','实际提款')->view('admin.field.copy_content');
            
            $show->field('info');
            $show->field('bet_amount','打码量');
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
        return Form::make(new Withdraw(), function (Form $form) {
            $form->display('id');
            $form->text('order_no');
            $form->text('card_id');
            $form->text('user_id');
            $form->text('amount');
            $form->text('cash_fee');
            $form->text('real_money');
            $form->text('info');
            $form->text('state');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
