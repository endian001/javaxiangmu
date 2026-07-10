<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\User;
use App\Models\AgentSettlement;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Hash;
use App\Admin\Actions\Grid\User\Fanyong;

class AgentController extends AdminController
{
    protected $title = "代理列表";
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->model()->where('isagent', 1);
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('username');
            // $grid->column('password');
            $grid->column('realname','姓名');
            // $grid->column('commisssion','佣金');
            $grid->column('settlement_id','结算方案')->display(function($settlement_id){
                $name = AgentSettlement::find($settlement_id)->name  ?? '';
                return $name;
            });

            $grid->column('balance','余额');

        
            $grid->column('status','状态')->using([1 => '正常',0 => '禁用']);
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('username');

            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new Fanyong());
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
            $form->text('password','密码')->creationRules('required|min:6|max:16',['required' => '请填写密码','min' => '密码最少6位数','max' => '密码最多16位']);
            $form->text('realname','真实姓名')->rules('required',['required' => '请填写真实姓名']);
            // $form->hidden('vip');
            // $form->hidden('level');
            $form->hidden('paypwd');
            // $form->text('isonline');
            $form->radio('allowagent','允许发展代理')->options([1 => '是',0 => '否'])->default(0);
            // $form->text('balance');
            // $form->text('mbalance');
            $form->text('phone','联系电话');
            $form->text('mail','邮箱');
            $form->hidden('fanshuifee');
            // $form->text('paysum');
            $form->radio('status','状态')->options([1 => '正常',0 => '禁用'])->default(1);
            // $form->text('isdel')->options([1 => '是',0 => '否'])->default(0);
            // $form->text('isblack')->options([1 => '是',0 => '否'])->default(0);
            // $form->text('lastip');
            // $form->text('logintime');
            // $form->text('sourceurl');
            $form->hidden('isagent')->default(1);
            $settlements = AgentSettlement::all();
            $options = [];
            foreach ($settlements as $v) {
                $options[$v->id] = $v->name;
            }
            $form->select('settlement_id','结算方案id')->options($options)->required();



            $form->saving(function (Form $form) {
                // 判断是否是新增操作
                //echo $form->settlement_id;
                $agent = AgentSettlement::where('id',$form->settlement_id)->first();
                //print_r($agent);
                $form->fanshuifee =  $agent->member_fs ;
                //exit;
                if ($form->isCreating()) {
                    $form->password = Hash::make($form->password);
                    $form->paypwd = $form->paypwd ? Hash::make($form->paypwd) : '';
                } else {
                    $form->password = $form->password ? Hash::make($form->password) : $form->model()->password;
                    $form->paypwd = ($form->paypwd && $form->model()->paypwd != $form->paypwd) ? Hash::make($form->paypwd) : $form->model()->paypwd;
                }

                $form->isagent = 1;

            });

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
