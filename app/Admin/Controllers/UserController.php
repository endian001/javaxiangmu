<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\User;
use App\Models\UserVip;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Illuminate\Support\Facades\Hash;
use Dcat\Admin\Http\Controllers\AdminController;

use App\Admin\Actions\Grid\User\Balance;
use App\Admin\Actions\Grid\User\BackBalance;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use App\Admin\Renderable\UserBalance;
use Dcat\Admin\Widgets\Card as WidgetsCard;
use App\Services\TgService;
use Dcat\Admin\Admin;

class UserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
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
        return Grid::make(new User(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->disableBatchDelete();
            $grid->column('id')->sortable();
            //$grid->column('pid');
            $grid->column('username')->view('admin.field.user_username');
            // $grid->column('password');
            $grid->column('realname');
            $grid->column('vip', '等级')->display(function ($vip) {
                $vipinfo = UserVip::find($vip);
                return ($vipinfo) ? $vipinfo->vipname : '注册会员';
            });
            //$grid->column('level');
            // $grid->column('paypwd');
            // $grid->column('isonline');
            $grid->column('isagent');
            $grid->column('isagent')->using([1 => '是', 0 => '否']);
            //$grid->column('allowagent');
            //$grid->column('reg_ip','注册IP');
            $grid->column('balance');
            $grid->column('mbalance');
            // $grid->column('phone');
            // $grid->column('mail');
            $grid->column('paysum');
            $grid->column('status')->using([1 => '正常', 0 => '禁用']);
            $grid->column('wallet_balance', '游戏余额')
                ->display('接口余额') // 设置按钮名称
                ->modal(function ($modal) {
                    // 设置弹窗标题
                    $modal->title('游戏余额');
                    $res = UserBalance::make(['id' => $this->username]);
                    return $res;
                });

            // $grid->column('status');
            // $grid->column('isdel');
            // $grid->column('isblack');
            $grid->column('lastip')->display(function (){
                return $this->lastip.'/'.$this->last_login_ip_address;
            });
            // $grid->column('logintime');
            // $grid->column('sourceurl');
            // $grid->column('loginsum');
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            //按钮筛选
            // $grid->selector(function (Grid\Tools\Selector $selector) {
            //     $selector->select('isagent', '代理会员', [
            //         0 => '非代理会员',
            //         1 => '代理会员',
            //     ]);
            //     $selector->select('isonline', '是否在线', [
            //         0 => '不在线',
            //         1 => '在线',
            //     ]);
            // });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                // append一个操作
                $actions->append(new Balance());
                $actions->append(new BackBalance());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('username');
                $filter->equal('isagent', '代理会员')->select([
                    0 => '非代理会员',
                    1 => '代理会员',
                ]);
                $filter->equal('isonline', '是否在线')->select([
                    0 => '不在线',
                    1 => '在线',
                ]);
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
            $show->field('isonline')->using([1 => '在线', 0 => '离线']);
            $show->field('isagent')->using([1 => '是', 0 => '否']);
            $show->field('allowagent')->using([1 => '是', 0 => '否']);
            $show->field('balance');
            $show->field('mbalance');
            $show->field('phone');
            $show->field('mail');
            $show->field('paysum');
            $show->field('status')->using([1 => '正常', 0 => '禁用']);
            $show->field('isdel')->using([1 => '是', 0 => '否']);
            $show->field('isblack')->using([1 => '是', 0 => '否']);
            $show->field('lastip');
            $show->logintime()->as(function ($logintime) {
                return date('Y-m-d H:i:s', $logintime);
            });
            $show->field('reg_ip');
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
                $form->text('username')->rules('required|unique:users|min:6', ['required' => '请填写用户名', 'unique' => '用户名重复','min' => '用户名至少6位']);
            } else {
                $form->display('username');
            }
            $form->password('password', '密码')->creationRules('required|min:6|max:16', ['required' => '请填写密码', 'min' => '密码最少6位数', 'max' => '密码最多16位']);
            $form->text('realname')->rules('required', ['required' => '请填写真实姓名']);

            $settlements = UserVip::all();
            $options = [];
            foreach ($settlements as $k => $v) {
                $options[$v->id] = $v->vipname;
            }
            $form->select('vip', '会员等级')->options($options);


            $form->text('level')->value(0);
            $form->password('paypwd', '支付密码')->creationRules('required', ['required' => '请填写支付密码']);
            // $form->text('isonline');
            $form->radio('isagent')->options([1 => '是', 0 => '否'])->default(0);

            $settlements = \App\Models\User::where('isagent', 1)->get();
            $options = [];
            foreach ($settlements as $k => $v) {
                $options[$v->id] = $v->username;
            }
            $form->select('pid', '上级代理')->options($options);

            $form->radio('allowagent')->options([1 => '是', 0 => '否'])->default(1);
            $form->text('balance')->disable()->default(0.0);
            $form->text('mbalance')->disable()->default(0.0);
            $form->text('phone');
            $form->text('mail');
            // $form->text('paysum');
            $form->radio('status')->options([1 => '正常', 0 => '禁用'])->default(1);
            $form->radio('isdel')->options([1 => '是', 0 => '否'])->default(0);
            $form->radio('isblack')->options([1 => '是', 0 => '否'])->default(0);
            // $form->text('lastip');
            // $form->text('logintime');
            $form->text('sourceurl');
            // $form->text('loginsum');

            $form->saving(function (Form $form) {
                // 判断是否是新增操作
                $form->vip = empty($form->vip) ? 1 :  $form->vip;
                if (OpsChangeAudit::hasAnyChanged($form, ['password', 'paypwd'], ['password', 'paypwd'])) {
                    OperationPermission::assert(OperationPermission::MEMBER_PASSWORD_RESET);
                }
                if (OpsChangeAudit::hasAnyChanged($form, ['isagent', 'pid', 'allowagent'])) {
                    OperationPermission::assert(OperationPermission::MEMBER_AGENT_UPDATE);
                }
                if (OpsChangeAudit::hasAnyChanged($form, ['status', 'isdel', 'isblack'])) {
                    OperationPermission::assert(OperationPermission::MEMBER_STATUS_UPDATE);
                }
                if (OpsChangeAudit::hasAnyChanged($form, ['vip', 'level'])) {
                    OperationPermission::assert(OperationPermission::MEMBER_VIP_UPDATE);
                }
                OpsChangeAudit::writeFormChanges('member.user.update', $form, [
                    'password' => 'login password',
                    'paypwd' => 'pay password',
                    'realname' => 'real name',
                    'vip' => 'vip level',
                    'level' => 'member level',
                    'isagent' => 'agent flag',
                    'pid' => 'parent agent',
                    'allowagent' => 'allow agent',
                    'phone' => 'phone',
                    'mail' => 'mail',
                    'status' => 'status',
                    'isdel' => 'deleted flag',
                    'isblack' => 'blacklist flag',
                    'sourceurl' => 'source url',
                ], ['password', 'paypwd'], 'username');
                if ($form->isCreating()) {
                    $form->settlement_id = 3;
                    $form->pid = intval($form->pid);
                    $form->password = Hash::make($form->password);
                    $form->paypwd = $form->paypwd ? Hash::make($form->paypwd) : '';

                } else {
                    if ($form->isagent) {
                        $form->settlement_id = 3;
                    }
                    $form->pid = intval($form->pid);
                    $form->password = ($form->password && $form->model()->password != $form->password) ? Hash::make($form->password) : $form->model()->password;
                    $form->paypwd = ($form->paypwd && $form->model()->paypwd != $form->paypwd) ? Hash::make($form->paypwd) : $form->model()->paypwd;
                }
            });

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
