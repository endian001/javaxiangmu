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
    protected $status = [1 => '启用', 0 => '停用'];
    protected $yesNo = [1 => '是', 0 => '否'];

    protected function grid()
    {
        return Grid::make(Activity::with(['type_data']), function (Grid $grid) {
            $grid->model()->orderBy('sort_order', 'desc')->orderBy('id', 'desc');
            $grid->column('id', 'ID')->sortable();
            $grid->column('type_data.name', '活动分类');
            $grid->column('title', '活动标题');
            $grid->column('sort_order', '排序')->sortable();
            $grid->column('starts_at', '开始时间');
            $grid->column('ends_at', '结束时间');
            $grid->column('is_popup', '首页弹窗')->using($this->status);
            $grid->column('can_apply', '允许申请')->using($this->yesNo);
            $grid->column('state', '电脑端状态')->using($this->status);
            $grid->column('app_state', '手机端状态')->using($this->status);
            $grid->column('created_at', '创建时间');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id', 'ID');
                $filter->equal('state', '电脑端状态')->select($this->status);
                $filter->equal('app_state', '手机端状态')->select($this->status);
                $filter->equal('is_popup', '首页弹窗')->select($this->status);
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Activity(), function (Show $show) {
            $labels = [
                'id' => 'ID',
                'type' => '活动分类',
                'title' => '中文标题',
                'entitle' => '前台泰文标题',
                'content' => '中文内容',
                'encontent' => '前台泰文内容',
                'memo' => '中文规则',
                'enmemo' => '前台泰文规则',
                'apply_count' => '申请次数',
                'sort_order' => '排序',
                'starts_at' => '开始时间',
                'ends_at' => '结束时间',
                'is_popup' => '首页弹窗',
                'popup_frequency' => '弹窗频率',
                'popup_delay_seconds' => '弹窗延迟秒数',
                'action_url' => '按钮跳转地址',
                'button_text' => '前台按钮文案',
                'requires_auth' => '点击需要登录',
                'can_apply' => '允许申请',
                'state' => '电脑端状态',
                'app_state' => '手机端状态',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ];

            foreach ($labels as $field => $label) {
                $show->field($field, $label);
            }

            $images = [
                'banner' => '电脑端卡片图',
                'app_img' => '手机端卡片图',
                'popup_image' => '电脑端弹窗图',
                'app_popup_image' => '手机端弹窗图',
                'detail_image' => '电脑端详情图',
                'app_detail_image' => '手机端详情图',
            ];

            foreach ($images as $field => $label) {
                $show->field($field, $label)->image();
            }
        });
    }

    protected function form()
    {
        return Form::make(new Activity(), function (Form $form) {
            $form->display('id', 'ID');

            $types = \App\Models\ActivityType::where('state', 1)->orderBy('sort_order', 'desc')->orderBy('id')->get();
            $options = [];
            foreach ($types as $type) {
                $options[$type->id] = $type->name;
            }

            $form->select('type', '活动分类')->options($options)->required();
            $form->text('title', '中文标题')->required();
            $form->text('entitle', '前台泰文标题')->required();
            $form->editor('content', '中文内容')->required();
            $form->editor('encontent', '前台泰文内容');
            $form->editor('memo', '中文规则')->required();
            $form->editor('enmemo', '前台泰文规则')->required();
            $form->number('apply_count', '申请次数')->default(0);
            $form->image('banner', '电脑端卡片图')->uniqueName();
            $form->image('app_img', '手机端卡片图')->uniqueName();
            $form->number('sort_order', '排序')->default(0);
            $form->datetime('starts_at', '开始时间');
            $form->datetime('ends_at', '结束时间');
            $form->radio('is_popup', '首页弹窗')->options($this->status)->default(0);
            $form->select('popup_frequency', '弹窗频率')->options([
                'always' => '每次访问都弹',
                'session' => '每次会话一次',
                'daily' => '每天一次',
                'once' => '仅首次打开一次',
            ])->default('once');
            $form->number('popup_delay_seconds', '弹窗延迟秒数')->default(0);
            $form->image('popup_image', '电脑端弹窗图')->uniqueName();
            $form->image('app_popup_image', '手机端弹窗图')->uniqueName();
            $form->image('detail_image', '电脑端详情图')->uniqueName();
            $form->image('app_detail_image', '手机端详情图')->uniqueName();
            $form->text('action_url', '按钮跳转地址');
            $form->text('button_text', '前台按钮文案');
            $form->radio('requires_auth', '点击需要登录')->options($this->yesNo)->default(0);
            $form->radio('can_apply', '允许申请')->options($this->yesNo)->default(1);
            $form->radio('state', '电脑端状态')->options($this->status)->default(1);
            $form->radio('app_state', '手机端状态')->options($this->status)->default(1);
            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            $form->saving(function (Form $form) {
                $contentFields = ['type', 'title', 'entitle', 'content', 'encontent', 'memo', 'enmemo', 'apply_count', 'banner', 'app_img', 'sort_order', 'starts_at', 'ends_at', 'popup_frequency', 'popup_delay_seconds', 'popup_image', 'app_popup_image', 'detail_image', 'app_detail_image', 'action_url', 'button_text'];
                $switchFields = ['can_apply', 'state', 'app_state', 'is_popup', 'requires_auth'];

                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $contentFields)) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
                }
                if ($form->isCreating() || OpsChangeAudit::hasAnyChanged($form, $switchFields)) {
                    OperationPermission::assert(OperationPermission::ACTIVITY_PUBLISH_SWITCH);
                }

                $auditFields = [
                    'type' => '活动分类',
                    'title' => '中文标题',
                    'entitle' => '前台泰文标题',
                    'content' => '中文内容',
                    'encontent' => '前台泰文内容',
                    'memo' => '中文规则',
                    'enmemo' => '前台泰文规则',
                    'apply_count' => '申请次数',
                    'banner' => '电脑端卡片图',
                    'app_img' => '手机端卡片图',
                    'sort_order' => '排序',
                    'starts_at' => '开始时间',
                    'ends_at' => '结束时间',
                    'is_popup' => '首页弹窗',
                    'popup_frequency' => '弹窗频率',
                    'popup_delay_seconds' => '弹窗延迟秒数',
                    'popup_image' => '电脑端弹窗图',
                    'app_popup_image' => '手机端弹窗图',
                    'detail_image' => '电脑端详情图',
                    'app_detail_image' => '手机端详情图',
                    'action_url' => '按钮跳转地址',
                    'button_text' => '前台按钮文案',
                    'requires_auth' => '点击需要登录',
                    'can_apply' => '允许申请',
                    'state' => '电脑端状态',
                    'app_state' => '手机端状态',
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
