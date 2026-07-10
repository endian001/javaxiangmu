<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\Userredpacket;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserredpacketController extends AdminController
{
    use ReadOnlyResource;

    protected $title = "会员红包领取列表";
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Userredpacket::with(['user_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id','ID')->sortable();
            $grid->column('user_data.username','用户名');
            $grid->column('redpacketfee','红包比例');
            $grid->column('money','充值金额');
            $grid->column('redpacketmoney','红包金额');
            $grid->column('status','状态')->using([0 => '待领取',1 => '已领取']);;
            $grid->column('usetime','领取时间');
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');

            });
            $grid->disableCreateButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableDelete();
                $actions->disableEdit();
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
        return Show::make($id, new Userredpacket(), function (Show $show) {
            $show->field('id');
            $show->field('id');
            $show->field('uid');
            $show->field('redpacketid');
            $show->field('redpacketfee');
            $show->field('money');
            $show->field('redpacketmoney');
            $show->field('status');
            $show->field('usetime');
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
        return Form::make(new Userredpacket(), function (Form $form) {
            $form->display('id');
            $form->text('uid');
            $form->text('redpacketid');
            $form->text('redpacketfee');
            $form->text('money');
            $form->text('redpacketmoney');
            $form->text('status');
            $form->text('usetime');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
