<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\User;
use App\User as AppUser;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Hash;

class FinanceReportController extends AdminController
{
    use ReadOnlyResource;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('isagent','代理')->using([1 => '是',0 => '否']);
            $grid->column('username');
            $grid->column('recharge_times','充值次数')->display(function (){
                return $this->agentrechargeTimes();
            });
            $grid->column('withdraw_times','提款次数')->display(function (){
                return $this->agentwithdrawTimes();
            });
            $grid->column('recharge_sum','总存款')->display(function (){
                return $this->agentrechargeSum();
            });
            $grid->column('withdraw_sum','总提现')->display(function (){
                return $this->agentwithdrawSum();
            });
            $grid->column('fanshui_sum','总反水')->display(function (){
                return AppUser::totalfanhui($this->id);
            });

            $grid->column('balance','账户余额');
            $grid->disableActions();
            $grid->disableCreateButton();
            //名字搜索
            $grid->quickSearch('username');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('username');
                $filter->between('created_at', '日期')->date();
                $filter->between('balance', '金额');
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
        return Show::make($id, new User(), function (Show $show) {
            $show->field('id');
            $show->field('fid');
            $show->field('username');
            // $show->field('password');
            $show->field('realname');
            $show->field('vip');
            $show->field('level');
            // $show->field('paypwd');
            $show->field('isonline')->using([1 => '在线',0 => '离线']);
            $show->field('isagent')->using([1 => '是',0 => '否']);
            $show->field('allowagent')->using([1 => '是',0 => '否']);
            $show->field('balance');
            $show->field('mbalance');
            $show->field('phone');
            $show->field('mail');
            $show->field('paysum');
            $show->field('status')->using([1 => '正常',0 => '禁用']);
            $show->field('isdel')->using([1 => '是',0 => '否']);
            $show->field('isblack')->using([1 => '是',0 => '否']);
            $show->field('lastip');
            $show->logintime()->as(function ($logintime) {
                return date('Y-m-d H:i:s',$logintime);
            });
            $show->field('sourceurl');
            $show->field('loginsum');
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
        return Form::make(new User(), function (Form $form) {
            $form->display('id');
            // $form->text('fid');
            if ($form->isCreating()) {
                $form->text('username')->rules('required|unique:users',['required' => '请填写用户名','unique' => '用户名重复']);
            } else {
                $form->display('username');
            }
            $form->text('password','密码')->creationRules('required,min:6,max:16',['required' => '请填写密码','min' => '密码最少6位数','max' => '密码最多16位']);
            $form->text('realname')->rules('required',['required' => '请填写真实姓名']);
            $form->text('vip');
            $form->text('level');
            $form->text('paypwd');
            // $form->text('isonline');
            $form->radio('isagent')->options([1 => '是',0 => '否'])->default(0);
            $form->radio('allowagent')->options([1 => '是',0 => '否'])->default(0);
            $form->text('balance');
            $form->text('mbalance');
            $form->text('phone');
            $form->text('mail');
            // $form->text('paysum');
            $form->radio('status')->options([1 => '正常',0 => '禁用'])->default(1);
            $form->text('isdel')->options([1 => '是',0 => '否'])->default(0);
            $form->text('isblack')->options([1 => '是',0 => '否'])->default(0);
            // $form->text('lastip');
            // $form->text('logintime');
            $form->text('sourceurl');
            // $form->text('loginsum');

            $form->saving(function (Form $form) {
                // 判断是否是新增操作
                if ($form->isCreating()) {
                    $form->password = Hash::make($form->password);
                    $form->paypwd = $form->paypwd ? Hash::make($form->paypwd) : '';
                } else {
                    $form->password = $form->password ? Hash::make($form->password) : $form->model()->password;
                    $form->paypwd = ($form->paypwd && $form->model()->paypwd != $form->paypwd) ? Hash::make($form->paypwd) : $form->model()->paypwd;
                }
            });

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
