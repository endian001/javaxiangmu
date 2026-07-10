<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\UserVip;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserVipController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new UserVip(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('vipname');

            //$grid->column('viptype');
            $grid->column('recharge','充值累计');
            $grid->column('flow','流水累计');
/*
            $grid->column('vrbetfee','Vebet返水(%)');
            $grid->column('ldfee','雷火返水(%)');*/
            $grid->column('realperson');
             $grid->column('electron');
            $grid->column('joker');
            $grid->column('sport');
            // $grid->column('fish');
            $grid->column('lottery');
            $grid->column('e_sport');
            $grid->column('upgrade_bonus', '升级奖励金额');
            $grid->column('weekly_salary', '周俸禄金额');
            $grid->column('monthly_salary', '月俸禄金额');
            $grid->column('status')->using([1 => '正常',0 => '禁用']);
            $grid->column('vippic','对应等级图片');
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

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
        // return Show::make($id, new UserVip(), function (Show $show) {
        //     $show->field('id');
        //     $show->field('vipname');
        //     $show->field('viptype');
        //     $show->field('realperson');
        //     $show->field('electron');
        //     $show->field('chessandcard');
        //     $show->field('sports');
        //     $show->field('fish');
        //     $show->field('lottery');
        //     $show->field('lottery6');
        //     $show->field('status');
        //     $show->field('exp');
        //     $show->field('isdefault');
        //     $show->field('isdel');
        //     $show->field('created_at');
        //     $show->field('updated_at');
        // });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new UserVip(), function (Form $form) {
            $form->display('id');
            $form->text('vipname')->required();
            $form->hidden('viptype')->default(1);
            $form->decimal('recharge','充值累计');
            $form->decimal('flow','流水累计');
            $form->decimal('electron');
            $form->decimal('realperson');
            $form->decimal('joker');
            $form->decimal('sport');
            // $form->decimal('fish');
            $form->decimal('lottery');
            $form->decimal('e_sport');
            $form->decimal('upgrade_bonus', '升级奖励金额')->default(0);
            $form->decimal('weekly_salary', '周俸禄金额')->default(0);
            $form->decimal('monthly_salary', '月俸禄金额')->default(0);
            $form->radio('status')->options([1 => '可用',0 => '禁用'])->default(1);
            //$form->number('exp');
            $form->radio('is_default')->options([1 => '是',0 => '否'])->default(0);
            $form->text('vippic');
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                $fields = [
                    'vipname',
                    'viptype',
                    'recharge',
                    'flow',
                    'electron',
                    'realperson',
                    'joker',
                    'sport',
                    'lottery',
                    'e_sport',
                    'upgrade_bonus',
                    'weekly_salary',
                    'monthly_salary',
                    'status',
                    'is_default',
                    'vippic',
                ];
                if (OpsChangeAudit::hasAnyChanged($form, $fields)) {
                    OperationPermission::assert(OperationPermission::MEMBER_VIP_UPDATE);
                }
                OpsChangeAudit::writeFormChanges('member.vip.config.update', $form, [
                    'vipname' => 'vip name',
                    'viptype' => 'vip type',
                    'recharge' => 'recharge total',
                    'flow' => 'flow total',
                    'electron' => 'slot rebate',
                    'realperson' => 'live rebate',
                    'joker' => 'board rebate',
                    'sport' => 'sport rebate',
                    'lottery' => 'lottery rebate',
                    'e_sport' => 'esport rebate',
                    'upgrade_bonus' => 'upgrade bonus',
                    'weekly_salary' => 'weekly salary',
                    'monthly_salary' => 'monthly salary',
                    'status' => 'status',
                    'is_default' => 'default flag',
                    'vippic' => 'vip image',
                ], [], 'vipname');
            });
        });
    }
}
