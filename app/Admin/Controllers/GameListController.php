<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\GameList;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Services\TgService;
use App\Admin\Tools\UrlEdit;
use App\Models\Api as ApiModel;
class GameListController extends AdminController
{
    protected $category = ['realbet' => '真人','sport' => '体育','concise' => '电子','gaming' => '电竞','joker' => '棋牌','lottery' => '彩票','fishing' => '捕鱼','lhc' => '六合彩','jsc' => '极速彩','jwc' => '境外彩','qkc' => '区块彩'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        // $tg = new TgService();
        // dd($tg->gameslist('ae'));
        return Grid::make(new GameList(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('platform_name');
            $grid->column('name');
            $grid->column('game_code');
            //$grid->column('game_icon')->image('',100,100);
            // $grid->column('name_en');
            // $grid->column('keywords');
            $grid->column('category_id')->using($this->category);
            // $grid->column('order_by');
            // $grid->column('state');
            $grid->column('is_top','前台公开')->using([1 => '是',0 => '否']);
            $grid->column('is_hot')->using([1 => '是',0 => '否']);
            $grid->column('is_new')->using([1 => '是',0 => '否']);
            $grid->column('is_recommend')->using([1 => '是',0 => '否']);
            // $grid->column('is_pc')->using([1 => '是',0 => '否']);
            // $grid->column('is_mobile')->using([1 => '是',0 => '否']);
            $grid->column('site_state')->using([1 => '正常',0 => '关闭']);
			$grid->column('app_state','APP状态')->using([1 => '正常',0 => '关闭']);
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('category_id')->select($this->category);
                $filter->equal('platform_name');
                $filter->equal('is_top','前台公开')->select([1 => '是', 0 => '否']);
                $filter->equal('app_state','APP状态')->select([1 => '正常', 0 => '关闭']);
                $filter->equal('site_state','站点状态')->select([1 => '正常', 0 => '关闭']);
                $filter->equal('is_hot')->select([1 => '是', 0 => '否']);
                $filter->equal('is_new')->select([1 => '是', 0 => '否']);
                $filter->equal('is_recommend')->select([1 => '是', 0 => '否']);
                $filter->like('name');
                $filter->like('game_code');
            });
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
                $actions->disableDelete();
              
            });
			//$grid->tools(new UrlEdit());
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
        return Show::make($id, new GameList(), function (Show $show) {
            $show->field('id');
            $show->field('platform_name');
            $show->field('name');
            $show->field('name_en');
            // $show->field('keywords');
            // $show->field('category_id');
            // $show->field('game_code');
            // $show->field('game_img');
            // $show->field('order_by');
            // $show->field('state');
            $show->field('is_hot');
            $show->field('is_new');
            $show->field('is_recommend');
            $show->field('is_top');
            // $show->field('is_pc');
            // $show->field('is_mobile');
            $show->field('site_state');
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
        $plat = ApiModel::query()
            ->orderBy('order_by')
            ->orderBy('api_code')
            ->pluck('api_name', 'api_code')
            ->toArray();

        if (empty($plat)) {
            try {
                $tg = new \App\Services\TgService();
                $gamelist = $tg->gamesalllist();
                foreach ($gamelist as $val) {
                    $plat[$val['platform_code']] = $val['platformname'];
                }
            } catch (\Throwable $e) {
                $plat = [];
            }
        }
        
        return Form::make(new GameList(), function (Form $form) use ($plat) {
            $form->display('id');
            $form->select('platform_name')->options($plat)->required();
            $form->text('name')->required();
            // $form->text('name_en')->required();
            // $form->text('keywords');
            $form->text('game_code')->required();
			$form->select('category_id')->options($this->category)->required();
			$form->image('api_logo_img','接口图标')->uniqueName();
            //$form->image('check_yes_img','PC选中状态')->uniqueName();
            //$form->image('check_no_img','PC未选中状态')->uniqueName();
            $form->text('mobile_img','手机端图片')->placeholder('输入图片地址');
			$form->text('app_img','APP图片')->placeholder('输入图片地址');
            $form->number('order_by')->default(0)->help("数字越小越靠前");
            $form->radio('is_top','前台公开')->options([1 => '是',0 => '否'])->default(1)->help('关闭后不会出现在前台游戏列表，也不会允许打开游戏');
            $form->radio('is_hot')->options([1 => '是',0 => '否'])->default(0);
            $form->radio('is_new')->options([1 => '是',0 => '否'])->default(0);
            $form->radio('is_recommend')->options([1 => '是',0 => '否'])->default(0);
            // $form->radio('is_pc')->options([1 => '是',0 => '否'])->default(1);
            // $form->radio('is_mobile')->options([1 => '是',0 => '否'])->default(1);
            $form->radio('site_state')->options([1 => '正常',0 => '关闭'])->default(1);
            $form->radio('app_state','APP状态')->options([1 => '正常',0 => '关闭'])->default(1);
        
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['platform_name', 'name', 'game_code', 'category_id', 'api_logo_img', 'mobile_img', 'app_img', 'order_by'])) {
                    OperationPermission::assert(OperationPermission::GAME_LIST_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['is_top', 'is_hot', 'is_new', 'is_recommend', 'site_state', 'app_state'])) {
                    OperationPermission::assert(OperationPermission::GAME_PUBLISH_SWITCH);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('game.list.create', $form, [
                        'platform_name' => 'platform',
                        'name' => 'game name',
                        'game_code' => 'game code',
                        'category_id' => 'category',
                        'api_logo_img' => 'api logo',
                        'mobile_img' => 'mobile image',
                        'app_img' => 'app image',
                        'order_by' => 'sort order',
                        'is_top' => 'frontend visible',
                        'is_hot' => 'hot flag',
                        'is_new' => 'new flag',
                        'is_recommend' => 'recommend flag',
                        'site_state' => 'site state',
                        'app_state' => 'app state',
                    ], [], 'name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('game.list.update', $form, [
                    'platform_name' => 'platform',
                    'name' => 'game name',
                    'game_code' => 'game code',
                    'category_id' => 'category',
                    'api_logo_img' => 'api logo',
                    'mobile_img' => 'mobile image',
                    'app_img' => 'app image',
                    'order_by' => 'sort order',
                    'is_top' => 'frontend visible',
                    'is_hot' => 'hot flag',
                    'is_new' => 'new flag',
                    'is_recommend' => 'recommend flag',
                    'site_state' => 'site state',
                    'app_state' => 'app state',
                ], [], 'name');
            });
        });
    }
}
