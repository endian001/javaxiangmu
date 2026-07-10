<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\GameRecord;
use App\Models\GameRecord as ModelsGameRecord;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Services\TgService;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Admin;

class GameRecordController extends AdminController
{
    use ReadOnlyResource;

    protected $status = [1 => '已结算',2 => '未结算',0 => '无效注单'];

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
        return Grid::make(new GameRecord(), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('username','用户名')->view('admin.field.user_username');
            $grid->column('bet_id');
            $grid->column('bet_time');
/*            $grid->column('platform_type')->display(function ($platform_type){
                return  $platform_type ;//  $this->gamelist['ag'];
            });*/
            //$grid->column('game_type');
            $tg = New TgService;
            $gamelist =$tg->getallgamename();
          
            $grid->column('platform_type')->using($gamelist);
            $grid->column('bet_amount','投注金额');
            $grid->column('win_loss');
            $grid->column('is_back')->using([1 => '已返水',0 => '未返水']);
            $grid->column('status')->using($this->status);
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->filter(function (Grid\Filter $filter) {
                            $tg = New TgService;
            $gamelist =$tg->getallgamename();
                $filter->equal('id');
                $filter->equal('username');
                $filter->equal('platform_type')->select($gamelist);
                $filter->between('bet_time', '日期')->date();
            });
            $grid->footer(function ($collection) use ($grid) {
                $query = ModelsGameRecord::query();
            
                // 拿到表格筛选 where 条件数组进行遍历
                $grid->model()->getQueries()->unique()->each(function ($value) use (&$query) {
                    if (in_array($value['method'], ['paginate', 'get', 'orderBy', 'orderByDesc'], true)) {
                        return;
                    }
            
                    $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
                });
            
                // 查出统计数据
                $data = $query->sum('valid_amount');
                // dd($data);
            
                return "<div style='padding: 10px;'>总收入 ： $data</div>";
            });

            $grid->footer(function ($collection) use ($grid) {
                // 本页统计
                $valid_amount = $collection->sum('valid_amount');
                $str = "<div class='pull-right'>";
                $str .= "本页有效投注金额总计:<span style='color:red;'>".$valid_amount."</span>";
                $win_loss = $collection->sum('win_loss');
                $str .= "&nbsp;&nbsp;&nbsp;本页输赢金额总计:<span style='color:red;'>".$win_loss."</span>";
                $str .= "</div><br>";
                // 全部统计
                $query = ModelsGameRecord::query();
            
                // 拿到表格筛选 where 条件数组进行遍历
                $grid->model()->getQueries()->unique()->each(function ($value) use (&$query) {
                    if (in_array($value['method'], ['paginate', 'get', 'orderBy', 'orderByDesc'], true)) {
                        return;
                    }
                    $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
                });
            
                // 查出统计数据
                $valid_amount_sum = $query->sum('valid_amount');
                $win_loss_sum = $query->sum('win_loss');
                $str .= "<div class='pull-right'>";
                $str .= "有效投注金额总计:<span style='color:red;'>".$valid_amount_sum."</span>";
                $str .= "&nbsp;&nbsp;&nbsp;输赢金额总计:<span style='color:red;'>".$win_loss_sum."</span>";
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
        return Show::make($id, new GameRecord(), function (Show $show) {
            $show->field('id');
            $show->field('user_id');
            $show->field('bet_id');
            $show->field('bet_time');
            $show->field('platform_type');
            $show->field('game_type');
            $show->field('bet_amount');
            $show->field('valid_amount');
            $show->field('win_loss');
            $show->field('is_back');
            $show->field('status');
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
        return Form::make(new GameRecord(), function (Form $form) {
            $form->display('id');
            $form->text('user_id');
            $form->text('bet_id');
            $form->text('bet_time');
            $form->text('platform_type');
            $form->text('game_type');
            $form->text('bet_amount');
            $form->text('valid_amount');
            $form->text('win_loss');
            $form->text('is_back');
            $form->text('status');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
