<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Article;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Models\Articlescate;
use Dcat\Admin\Http\Controllers\AdminController;

class ArticleController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Article(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('cateid')->display(function($cateid){
                $vipinfo = Articlescate::find($cateid);
                return ($vipinfo) ? $vipinfo->name : '注册会员';
            });
            $grid->column('created_at');
            $grid->column('updated_at');
            $grid->column('stor');

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
        return Show::make($id, new Article(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('cateid');
            $show->field('content');
            $show->field('stor');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Article(), function (Form $form) {
            $form->display('id');
            $form->text('name');
            $settlements = Articlescate::all();
            $options = [];
            foreach ($settlements as $k => $v) {
                $options[$v->id] = $v->name;
            }
            $form->select('cateid','文章分类')->options($options);
            $form->editor('content')->required();
            $form->text('stor');
            $form->saving(function (Form $form) {
                $fields = ['name', 'cateid', 'content', 'stor'];
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $fields)) {
                    OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('ops.article.create', $form, [
                        'name' => 'article name',
                        'cateid' => 'category',
                        'content' => 'content',
                        'stor' => 'sort order',
                    ], [], 'name');
                } else {
                    OpsChangeAudit::writeFormChanges('ops.article.update', $form, [
                        'name' => 'article name',
                        'cateid' => 'category',
                        'content' => 'content',
                        'stor' => 'sort order',
                    ], [], 'name');
                }
                if ($form->isCreating()) {
                    $form->created_at = date('Y-m-d H:i:s');
                    $form->updated_at = date('Y-m-d H:i:s');
                } else {
                    $form->updated_at = date('Y-m-d H:i:s');
                }
            });
        });
    }
}
