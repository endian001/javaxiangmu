<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Activity;
use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Show;

class ActivityController extends AdminController
{
    protected $status = [1 => 'Enabled', 0 => 'Disabled'];

    protected function grid()
    {
        return Grid::make(Activity::with(['type_data']), function (Grid $grid) {
            $grid->model()->orderBy('sort_order', 'desc')->orderBy('id', 'desc');
            $grid->column('id')->sortable();
            $grid->column('type_data.name', 'Category');
            $grid->column('title', 'Legacy title');
            $grid->column('entitle', 'Thai title');
            $grid->column('sort_order', 'Sort')->sortable();
            $grid->column('starts_at', 'Start');
            $grid->column('ends_at', 'End');
            $grid->column('is_popup', 'Home popup')->using([1 => 'Yes', 0 => 'No']);
            $grid->column('can_apply', 'Can apply')->using([1 => 'Yes', 0 => 'No']);
            $grid->column('state', 'Desktop')->using($this->status);
            $grid->column('app_state', 'Mobile')->using($this->status);
            $grid->column('created_at');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('state', 'Desktop')->select($this->status);
                $filter->equal('app_state', 'Mobile')->select($this->status);
                $filter->equal('is_popup', 'Home popup')->select([1 => 'Yes', 0 => 'No']);
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Activity(), function (Show $show) {
            foreach ([
                'id',
                'type',
                'title',
                'entitle',
                'content',
                'encontent',
                'memo',
                'enmemo',
                'apply_count',
                'sort_order',
                'starts_at',
                'ends_at',
                'is_popup',
                'popup_frequency',
                'popup_delay_seconds',
                'action_url',
                'requires_auth',
                'can_apply',
                'state',
                'app_state',
                'created_at',
                'updated_at',
            ] as $field) {
                $show->field($field);
            }
            foreach (['banner', 'app_img', 'popup_image', 'app_popup_image', 'detail_image', 'app_detail_image'] as $field) {
                $show->field($field)->image();
            }
        });
    }

    protected function form()
    {
        return Form::make(new Activity(), function (Form $form) {
            $form->display('id');

            $types = \App\Models\ActivityType::where('state', 1)->orderBy('sort_order', 'desc')->orderBy('id')->get();
            $options = [];
            foreach ($types as $type) {
                $options[$type->id] = $type->name;
            }

            $form->select('type', 'Category')->options($options)->required();
            $form->text('title', 'Legacy title')->required();
            $form->text('entitle', 'Thai title')->required();
            $form->editor('content', 'Legacy content')->required();
            $form->editor('encontent', 'Thai content');
            $form->editor('memo', 'Legacy rules')->required();
            $form->editor('enmemo', 'Thai rules')->required();
            $form->number('apply_count', 'Apply count')->default(0);
            $form->image('banner', 'Desktop card image')->uniqueName();
            $form->image('app_img', 'Mobile card image')->uniqueName();
            $form->number('sort_order', 'Sort')->default(0);
            $form->datetime('starts_at', 'Start time');
            $form->datetime('ends_at', 'End time');
            $form->radio('is_popup', 'Home popup')->options([1 => 'Yes', 0 => 'No'])->default(0);
            $form->select('popup_frequency', 'Popup frequency')->options([
                'always' => 'Every visit',
                'session' => 'Once per session',
                'daily' => 'Once per day',
                'once' => 'Only once',
            ])->default('daily');
            $form->number('popup_delay_seconds', 'Popup delay seconds')->default(0);
            $form->image('popup_image', 'Desktop popup image')->uniqueName();
            $form->image('app_popup_image', 'Mobile popup image')->uniqueName();
            $form->image('detail_image', 'Desktop detail image')->uniqueName();
            $form->image('app_detail_image', 'Mobile detail image')->uniqueName();
            $form->text('action_url', 'Action URL');
            $form->radio('requires_auth', 'Action requires login')->options([1 => 'Yes', 0 => 'No'])->default(0);
            $form->radio('can_apply', 'Can apply')->options([1 => 'Yes', 0 => 'No'])->default(1);
            $form->radio('state', 'Desktop status')->options($this->status)->default(1);
            $form->radio('app_state', 'Mobile status')->options($this->status)->default(1);
            $form->display('created_at');
            $form->display('updated_at');

            $form->saving(function (Form $form) {
                $contentFields = ['type', 'title', 'entitle', 'content', 'encontent', 'memo', 'enmemo', 'apply_count', 'banner', 'app_img', 'sort_order', 'starts_at', 'ends_at', 'popup_frequency', 'popup_delay_seconds', 'popup_image', 'app_popup_image', 'detail_image', 'app_detail_image', 'action_url'];
                $switchFields = ['can_apply', 'state', 'app_state', 'is_popup', 'requires_auth'];

                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $contentFields)) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $switchFields)) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_PUBLISH_SWITCH);
                }

                $auditFields = [
                    'type' => 'activity type',
                    'title' => 'legacy title',
                    'entitle' => 'thai title',
                    'content' => 'legacy content',
                    'encontent' => 'thai content',
                    'memo' => 'legacy rules',
                    'enmemo' => 'thai rules',
                    'apply_count' => 'apply count',
                    'banner' => 'desktop card image',
                    'app_img' => 'mobile card image',
                    'sort_order' => 'sort order',
                    'starts_at' => 'start time',
                    'ends_at' => 'end time',
                    'is_popup' => 'home popup',
                    'popup_frequency' => 'popup frequency',
                    'popup_delay_seconds' => 'popup delay',
                    'popup_image' => 'desktop popup image',
                    'app_popup_image' => 'mobile popup image',
                    'detail_image' => 'desktop detail image',
                    'app_detail_image' => 'mobile detail image',
                    'action_url' => 'action url',
                    'requires_auth' => 'requires login',
                    'can_apply' => 'can apply',
                    'state' => 'desktop state',
                    'app_state' => 'mobile state',
                ];

                if ($form->isCreating()) {
                    OpsChangeAudit::writeFormSnapshot('activity.config.create', $form, $auditFields, [], 'title');
                    return;
                }
                OpsChangeAudit::writeFormChanges('activity.config.update', $form, $auditFields, [], 'title');
            });
        });
    }
}
