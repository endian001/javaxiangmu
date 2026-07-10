<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\TransferLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Services\TgService;

class FanshuiLogController extends AdminController
{
    use ReadOnlyResource;

    protected $title = '返水记录';
    
    /**
     * Make a grid builder.
     * @return Grid
     */
    protected function grid()
    {
        $tg = New TgService;
        $gamelist =$tg->getallgamename();
        // dd($gamelist);
        return Grid::make(TransferLog::with(['user_data']), function (Grid $grid) use ($gamelist) {
            $grid->model()->where('transfer_type', 6)->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('user_data.username','用户名');
            $grid->column('platform_type','平台名称')->display(function ($platform_type) use ($gamelist){
                return $gamelist[$platform_type] ?? '';
            });
            $grid->column('state','状态')->using([1 => '已领取',0 => '未领取']);
            $grid->column('money','返水金额');
            $grid->column('created_at');
            $grid->column('updated_at','领取时间')->display(function (){
                return ($this->state) ? date('Y-m-d H:i:s',strtotime($this->updated_at)) : ' 暂无领取';
            });
            
            // 
           
            $grid->disableActions();
            $grid->disableCreateButton();
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('user_data.username','用户名');
                $filter->between('created_at')->datetime();
                
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
        return Show::make($id, new TransferLog(), function (Show $show) {
            $show->field('id');
            $show->field('order_no');
            $show->field('api_type');
            $show->field('user_id');
            $show->field('transfer_type');
            $show->field('money');
            $show->field('cash_fee');
            $show->field('real_money');
            $show->field('before_money');
            $show->field('after_money');
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
        return Form::make(new TransferLog(), function (Form $form) {
            $form->display('id');
            $form->text('order_no');
            $form->text('api_type');
            $form->text('user_id');
            $form->text('transfer_type');
            $form->text('money');
            $form->text('cash_fee');
            $form->text('real_money');
            $form->text('before_money');
            $form->text('after_money');
            $form->text('state');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
