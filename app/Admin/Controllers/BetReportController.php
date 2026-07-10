<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\Concerns\ReadOnlyResource;
use App\Admin\Repositories\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Hash;

class BetReportController extends AdminController
{
    use ReadOnlyResource;

    protected $title = '下注统计';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('username');
            $grid->column('bet_times','下注次数')->display(function (){
                return $this->betTimes();
            });
            $grid->column('bet_sum','投注总金额')->display(function (){
                return $this->betSum();
            });
            $grid->column('valid_bet_sum','有效投注总金额')->display(function (){
                return $this->vaildBetSum();
            });
            $grid->column('win_loss','输赢总金额')->display(function (){
                return $this->winLoss();
            });

            $grid->disableActions();
            $grid->disableCreateButton();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('username');
        
            });
        });
    }
}
