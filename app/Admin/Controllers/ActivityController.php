<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Activity;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
class ActivityController extends AdminController
{

    protected $type = [1 => '充值赠送'];
	protected $status = [1 => '正常',0 => '禁用'];
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(Activity::with(['type_data']), function (Grid $grid) {
            $grid->model()->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            
            $grid->column('type_data.name','活动类型');
            $grid->column('title');
            $grid->column('entitle');
            //$grid->column('content');
            $grid->column('apply_count');
            //$grid->column('banner');
            $grid->column('can_apply')->using([1 => '可申请',0 => '不可申请']);
            $grid->column('state')->using([1 => '正常',0 => '禁用']);
			$grid->column('app_state','APP状态')->using([1 => '正常',0 => '禁用']);
            $grid->column('created_at');
            // $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
				$filter->equal('state')->select($this->status);
				$filter->equal('app_state','APP状态')->select($this->status);

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
        return Show::make($id, new Activity(), function (Show $show) {
            $show->field('id');
            $show->field('type');
            $show->field('title');
            $show->field('entitle');
            $show->field('content');
            $show->field('apply_count');
            $show->field('banner')->image();
            $show->field('can_apply');
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
        return Form::make(new Activity(), function (Form $form) {

            $form->display('id');
            
            $settlements = \App\Models\ActivityType::where('state', 1)->get();
            $options = [];
            foreach ($settlements as $k => $v) {
                $options[$v->id] = $v->name;
            }
            
            $form->select('type')->options($options)->required();
            $form->text('title')->required();
            $form->text('entitle')->required();
            $form->editor('content')->required();
            $form->editor('memo','活动条款与规则')->required();
            $form->editor('enmemo','活动条款与规则')->required();
            $form->number('apply_count');
            $form->image('banner')->uniqueName();
			$form->image('app_img','APP图片')->uniqueName();
            $form->radio('can_apply')->options([1 => '可申请',0 => '不可申请'])->default(1);
            $form->radio('state')->options([1 => '正常',0 => '禁用'])->default(1);
            $form->radio('app_state','APP状态')->options([1 => '正常',0 => '禁用'])->default(1);
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['type', 'title', 'entitle', 'content', 'memo', 'enmemo', 'apply_count', 'banner', 'app_img'])) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, ['can_apply', 'state', 'app_state'])) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_PUBLISH_SWITCH);
                }
                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('activity.config.create', $form, [
                        'type' => 'activity type',
                        'title' => 'title',
                        'entitle' => 'english title',
                        'content' => 'content',
                        'memo' => 'rules',
                        'enmemo' => 'english rules',
                        'apply_count' => 'apply count',
                        'banner' => 'banner',
                        'app_img' => 'app image',
                        'can_apply' => 'can apply',
                        'state' => 'state',
                        'app_state' => 'app state',
                    ], [], 'title');
                    return;
                }
                OpsChangeAudit::writeFormChanges('activity.config.update', $form, [
                    'type' => 'activity type',
                    'title' => 'title',
                    'entitle' => 'english title',
                    'content' => 'content',
                    'memo' => 'rules',
                    'enmemo' => 'english rules',
                    'apply_count' => 'apply count',
                    'banner' => 'banner',
                    'app_img' => 'app image',
                    'can_apply' => 'can apply',
                    'state' => 'state',
                    'app_state' => 'app state',
                ], [], 'title');
            });
			
        });
    }
}
