<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\TransferLog;
use App\Models\TransferLog as ModelsTransferLog;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Services\TgService;
class TransferLogController extends AdminController
{
    use ReadOnlyResource;

    protected $transfer_type = [1 => '转出',0 => '转入',3 => '管理员增加',4 => '管理员扣除'];
    protected $state = [
        0 => '失败',
        1 => '成功',
        2 => '失败',
        3 => '处理中',
        4 => '上游成功待入账',
        5 => '待对账',
    ];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(TransferLog::with(['user_data']), function (Grid $grid) {
            $grid->model()->whereIN('transfer_type', [0,1,3,4])->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            //$grid->column('order_no');
            // $grid->column('api_type');
            
            $tg = New TgService;
            $gamelist =$tg->gamesalllist();        
            $game = [];
            foreach ($gamelist as $val){
               $game[$val['platform_code']]  = $val['platformname'];
            }
            $grid->column('api_type')->using($game);
            $grid->column('user_data.username','用户名');
            $grid->column('transfer_type')->using($this->transfer_type);
            $grid->column('money');
            //$grid->column('cash_fee');
            //$grid->column('real_money');
            $grid->column('before_money');
            $grid->column('after_money');
            $grid->column('state')->using($this->state);
            $grid->column('recovery_status', '回收状态');
            $grid->column('external_status', '上游状态');
            $grid->column('posted_at', '入账时间');
            $grid->column('remark','备注');
            $grid->column('created_at');

            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->filter(function (Grid\Filter $filter) {
            $tg = New TgService;
            $gamelist =$tg->gamesalllist();        
            $game = [];
            foreach ($gamelist as $val){
               $game[$val['platform_code']]  = $val['platformname'];
            }                
                $filter->equal('id');
                $filter->equal('user_data.username','用户名');
                $filter->between('created_at')->datetime();
                $filter->equal('api_type')->select($game);
                $filter->equal('transfer_type')->select($this->transfer_type);
                
            });

            $grid->footer(function ($collection) use ($grid) {
                // 本页统计
                $transfer_in = $collection->where('transfer_type',0)->sum('money');
                $str = "<div class='pull-right'>";
                $str .= "本页总计转出：<span style='color:red;'>".$transfer_in."</span>";
                $transfer_out = $collection->where('transfer_type',1)->sum('money');
                $str .= "&nbsp;&nbsp;&nbsp;本页总计转入：<span style='color:red;'>".$transfer_out."</span>";
                $str .= "</div><br>";
                // 全部统计
                $query = ModelsTransferLog::query();
                // 拿到表格筛选 where 条件数组进行遍历
                $grid->model()->getQueries()->unique()->each(function ($value) use (&$query) {
                    if (in_array($value['method'], ['paginate', 'get', 'orderBy', 'orderByDesc'], true)) {
                        return;
                    }
                    $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
                });
                $transfer_in_all = $query->where('transfer_type',0)->sum('money');
                
                $query = ModelsTransferLog::query();
                $grid->model()->getQueries()->unique()->each(function ($value) use (&$query) {
                    if (in_array($value['method'], ['paginate', 'get', 'orderBy', 'orderByDesc'], true)) {
                        return;
                    }
                    $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
                });
                $transfer_out_all = $query->where('transfer_type',1)->sum('money');

                $str .= "<div class='pull-right'>";
                $str .= "总计转出：<span style='color:red;'>".$transfer_out_all."</span>";
                $str .= "&nbsp;&nbsp;&nbsp;总计转入：<span style='color:red;'>".$transfer_in_all."</span>";
                $str .= "</div>";
                return $str;
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
            $show->field('state')->using($this->state);
            $show->field('recovery_status');
            $show->field('external_status');
            $show->field('external_checked_at');
            $show->field('posted_at');
            $show->field('reconcile_note');
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
