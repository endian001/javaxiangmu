<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Banner;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class BannerController extends AdminController
{
    protected $state = [1 => '显示',0 => '禁用'];

    protected $type = [1 => 'PC',2 => '手机'];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Banner(), function (Grid $grid) {
            $grid->model()->orderBy('id','desc');
            $grid->column('id')->sortable();
            $grid->column('type')->using($this->type);
            $grid->column('title');
            $grid->column('pic')->image('',100,100);
            $grid->column('jump_url');
            $grid->column('order');
            $grid->column('state')->using($this->state);
            $grid->column('created_at');
        
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
        return Show::make($id, new Banner(), function (Show $show) {
            $show->field('id');
            $show->field('type');
            $show->field('title');
            $show->field('pic');
            $show->field('jump_url');
            $show->field('order');
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
        return Form::make(new Banner(), function (Form $form) {
            $form->display('id');
            $form->radio('type')->options($this->type)->required();
            $form->text('title');
            $form->image('pic')->uniqueName()->required();
            $form->text('jump_url');
            $form->number('order')->default(0);
            $form->radio('state')->options($this->state)->required();
        
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                $fields = ['type', 'title', 'pic', 'jump_url', 'order', 'state'];
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $fields)) {
                    OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('ops.banner.create', $form, [
                        'type' => 'banner type',
                        'title' => 'title',
                        'pic' => 'image',
                        'jump_url' => 'jump url',
                        'order' => 'sort order',
                        'state' => 'state',
                    ], [], 'title');
                    return;
                }
                OpsChangeAudit::writeFormChanges('ops.banner.update', $form, [
                    'type' => 'banner type',
                    'title' => 'title',
                    'pic' => 'image',
                    'jump_url' => 'jump url',
                    'order' => 'sort order',
                    'state' => 'state',
                ], [], 'title');
            });
        });
    }
}
