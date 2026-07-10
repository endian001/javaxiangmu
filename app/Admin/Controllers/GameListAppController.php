<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\GameListApp;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Services\TgService;
use App\Admin\Tools\UrlEdit;
class GameListAppController extends AdminController
{
    protected $category = ['realbet' => '真人','sport' => '体育','concise' => '电子','gaming' => '电竞','joker' => '棋牌','lottery' => '彩票','fishing' => '热门'];
    protected $is_hot = [0 => '非热门',1 => '热门']; 	
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        // $tg = new TgService();
        // dd($tg->gameslist('ae'));
        return Grid::make(new GameListApp(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('platform_name');
            $grid->column('name');
            $grid->column('game_code');
            $grid->column('category_id')->using($this->category);
            $grid->column('app_state')->using([1 => '正常',0 => '关闭']);
            $grid->column('created_at');
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('category_id')->select($this->category);
                $filter->like('name');
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
                $actions->disableDelete();
              
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
        return Show::make($id, new GameListApp(), function (Show $show) {
            $show->field('id');
            $show->field('platform_name');
            $show->field('name');
            $show->field('name_en');
            $show->field('app_state');
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
        
        return Form::make(new GameListApp(), function (Form $form) {
            $plat = [];
            $form->display('id');
            $form->text('platform_name')->required();
            $form->text('name')->required();
            $form->text('game_code')->required();
			$form->select('category_id')->options($this->category)->required();
            $form->image('app_img','APP图片')->uniqueName();
            $form->number('order_by','排序')->default(0)->help("数字越小越靠前");
            $form->radio('app_state')->options([1 => '正常',0 => '关闭'])->default(1);
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['platform_name', 'name', 'game_code', 'category_id', 'app_img', 'order_by'])) {
                    OperationPermission::assert(OperationPermission::GAME_LIST_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['app_state'])) {
                    OperationPermission::assert(OperationPermission::GAME_PUBLISH_SWITCH);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('game.app.list.create', $form, [
                        'platform_name' => 'platform',
                        'name' => 'game name',
                        'game_code' => 'game code',
                        'category_id' => 'category',
                        'app_img' => 'app image',
                        'order_by' => 'sort order',
                        'app_state' => 'app state',
                    ], [], 'name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('game.app.list.update', $form, [
                    'platform_name' => 'platform',
                    'name' => 'game name',
                    'game_code' => 'game code',
                    'category_id' => 'category',
                    'app_img' => 'app image',
                    'order_by' => 'sort order',
                    'app_state' => 'app state',
                ], [], 'name');
            });
        });
    }
}
