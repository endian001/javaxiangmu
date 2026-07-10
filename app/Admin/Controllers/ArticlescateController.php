<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Articlescate;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class ArticlescateController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Articlescate(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
        
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
        return Show::make($id, new Articlescate(), function (Show $show) {
            $show->field('id');
            $show->field('name');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Articlescate(), function (Form $form) {
            $form->display('id');
            $form->text('name');
            $form->saving(function (Form $form) {
                $fields = ['name'];
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $fields)) {
                    OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('ops.article.category.create', $form, [
                        'name' => 'category name',
                    ], [], 'name');
                    return;
                }
                OpsChangeAudit::writeFormChanges('ops.article.category.update', $form, [
                    'name' => 'category name',
                ], [], 'name');
            });
        });
    }
}
