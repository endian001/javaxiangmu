<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Hash;
use App\Admin\Actions\Grid\User\Fanyong;
use App\Admin\Tools\AgentFanyong;


class AgentCommissionController extends AdminController
{
    use ReadOnlyResource;

    protected $title = '代理佣金报表';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->model()->where('isagent',1);
            // $grid->column('id')->sortable();
            $grid->column('username');
            //$grid->column('结算方案');
            $grid->column('child_count','总笔数')->display(function (){
                // return $this->agentbetTimes();
                return \app\User::gamecount($this->id);
            });
            $grid->column('bet_sum','总投注')->display(function (){
                return $this->amountsum($this->id,'bet_amount');
            });
            $grid->column('valid_bet_sum','总有效投注')->display(function (){
                return $this->amountsum($this->id,'valid_amount');
            });
            $grid->column('win_loss','总盈利')->display(function (){
                return $this->amountsum($this->id,'win_loss');
            });
            $grid->column('child_money','总佣金')->display(function (){
                // return $this->agentbetTimes();
                return \app\User::gamemoney($this->id);
            });			
            // $grid->column('win_loss','总获返利')->display(function (){
            //     return \app\User::Agentyongjin2($this->id);
            //     // return $this->agentwinLoss();
            // });
            // $grid->disableActions();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->append(new Fanyong());
                $actions->disableDelete();
                $actions->disableView();
                $actions->disableEdit();
            });
            $grid->disableCreateButton();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('username');
            });
            $grid->tools(new AgentFanyong());
        });
    }
    

}
