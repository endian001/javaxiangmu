<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\Recharge;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Admin\Actions\Grid\Recharge\Pass;
use App\Admin\Actions\Grid\Recharge\Refuse;
use Dcat\Admin\Admin;
use App\Models\Recharge as ModelsRecharge;

class RechargeController extends AdminController
{
    use ReadOnlyResource;

    protected $pay_way = [1 => '银行卡转账', 2 => 'ZGPAY支付', 3 => '支付宝扫描支付', 4 => '微信扫描支付', 5 => 'USDT-TRC20',6 => 'USDT-ERC20', 7 => 'EBpay',10 => '充值赠送',11 => '代理充值'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        //Admin::js('/admin/js/recharge_apply.js');
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
        return Grid::make(Recharge::with(['user_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            //$grid->column('order_no');
            //$grid->column('out_trade_no');
            $grid->column('user_data.username', '用户名')->view('admin.field.user_username');
            //$grid->column('amount');
            
            $grid->column('amount','充值金额');
            $grid->column('pay_way')->using($this->pay_way);
            //$grid->column('usdt_type','协议类型');
            $grid->column('usdt_rate','汇率');
            $grid->column('real_money','实际支付');

            
            
            //$grid->column('bank_no');
            $grid->column('state')->using([1 => '待审核', 2 => '已完成', 3 => '已拒绝']);
            $grid->column('created_at');
            
            $grid->footer(function ($collection) use ($grid) {
                // 本页统计
                $str = "<div class='pull-right'>";
                $real_money = $collection->where('state',2)->sum('real_money');
                $str .= "&nbsp;&nbsp;&nbsp;本页实际支付：<span style='color:red;'>".$real_money."</span>";
                $str .= "</div><br>";
                // 全部统计
                $query = ModelsRecharge::query();
                // 拿到表格筛选 where 条件数组进行遍历
                $grid->model()->getQueries()->unique()->each(function ($value) use (&$query) {
                    if (in_array($value['method'], ['paginate', 'get', 'orderBy', 'orderByDesc'], true)) {
                        return;
                    }
                    $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
                });
                $total_real_money = $query->where('state',2)->sum('real_money');
                $str .= "<div class='pull-right'>";
                $str .= "&nbsp;&nbsp;&nbsp;总计实际支付：<span style='color:red;'>".$total_real_money."</span>";
                $str .= "</div>";
                return $str;
            });

            //名字搜索
            // $grid->quickSearch('user_data.username');


            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('user_data.username', "用户名");
                $filter->between('created_at', '日期')->date();
                $filter->equal('pay_way')->select($this->pay_way);
            });
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableEdit();
                $actions->disableDelete();
                if ($actions->row->state == 1) {
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
        return Show::make($id, new Recharge(), function (Show $show) {
            $show->field('id');
            $show->field('order_no');
            $show->field('out_trade_no');
            $show->field('user_id');
            $show->field('amount');
            $show->field('cash_fee');
            $show->field('real_money');
            $show->field('pay_way')->using($this->pay_way);
            
            $show->field('bank_no','银行卡号');
            $show->field('bank');
            $show->field('bank_address');
            $show->field('bank_owner','姓名/USDT地址');
            $show->field('info');
            $show->field('state')->using([1 => '待审核', 2 => '已完成', 3 => '已拒绝']);
            $show->field('created_at');

            $show->panel()
                ->tools(function ($tools) {
                    $tools->disableEdit();
                });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Recharge(), function (Form $form) {
            $form->display('id');
            $form->text('order_no');
            $form->text('out_trade_no');
            $form->text('user_id');
            $form->text('amount');
            $form->text('cash_fee');
            $form->text('real_money');
            $form->text('pay_way');
            $form->text('bank');
            $form->text('bank_no');
            $form->text('bank_address');
            $form->text('bank_owner');
            $form->text('info');
            $form->text('state');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
