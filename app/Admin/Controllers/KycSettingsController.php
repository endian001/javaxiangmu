<?php

namespace App\Admin\Controllers;

use App\Admin\Services\KycSettingsService;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KycSettingsController extends Controller
{
    private $service;

    public function __construct(KycSettingsService $service)
    {
        $this->service = $service;
    }

    public function fields(Content $content, Request $request)
    {
        return $this->render($content, $request, '610110');
    }

    public function rules(Content $content, Request $request)
    {
        return $this->render($content, $request, '290000');
    }

    public function content(Content $content, Request $request)
    {
        return $this->render($content, $request, '290004');
    }

    public function saveField(Request $request, $id = null)
    {
        $request->validate([
            'field_key' => ($id ? 'nullable' : 'required').'|string|max:80',
            'default_label' => ($id ? 'nullable' : 'required').'|string|max:191',
            'custom_label' => 'nullable|string|max:191',
            'category' => 'nullable|in:identity,social',
            'input_type' => 'nullable|in:input,select,date',
            'format_rule' => 'nullable|in:any,email,date',
            'mask_mode' => 'nullable|in:plain,partial,masked',
            'min_length' => 'nullable|integer|min:0|max:255',
            'max_length' => 'nullable|integer|min:0|max:255',
            'position' => 'nullable|integer|min:0|max:10000',
        ]);

        $record = $id
            ? DB::table('kyc_profile_fields')->where('id', $id)->first()
            : null;
        if ($id && !$record) {
            return $this->error('栏位不存在或已删除', 404);
        }

        $values = $this->service->filterField($request->all());
        if (isset($values['custom_label']) && $values['custom_label'] === '') {
            $values['custom_label'] = null;
        }
        if (isset($values['options'])) {
            $values['options'] = json_encode(
                $values['options'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        if (
            isset($values['min_length'], $values['max_length'])
            && $values['max_length'] > 0
            && $values['min_length'] > $values['max_length']
        ) {
            return $this->error('最小字元数不能大于最大字元数', 422);
        }

        $now = now();
        $values['updated_by'] = $this->currentAdminId();
        $values['updated_at'] = $now;

        if ($record) {
            if ($record->is_system) {
                unset($values['field_key'], $values['default_label']);
            } elseif (isset($values['field_key'])) {
                $exists = DB::table('kyc_profile_fields')
                    ->where('field_key', $values['field_key'])
                    ->where('id', '<>', $id)
                    ->exists();
                if ($exists) {
                    return $this->error('栏位 ID 已存在', 422);
                }
            }
            DB::table('kyc_profile_fields')->where('id', $id)->update($values);
            $action = 'kyc.field.update';
        } else {
            $fieldKey = $values['field_key'] ?? '';
            if (DB::table('kyc_profile_fields')->where('field_key', $fieldKey)->exists()) {
                return $this->error('栏位 ID 已存在', 422);
            }
            $values = array_merge([
                'custom_label' => null,
                'category' => 'identity',
                'input_type' => 'input',
                'kyc_enabled' => 0,
                'frontend_visible' => 0,
                'required' => 0,
                'player_editable' => 0,
                'unique_value' => 0,
                'format_rule' => 'any',
                'min_length' => 0,
                'max_length' => 255,
                'mask_mode' => 'masked',
                'options' => json_encode([], JSON_UNESCAPED_UNICODE),
                'position' => 0,
                'status' => 1,
            ], $values);
            $values['is_system'] = 0;
            $values['created_at'] = $now;
            $id = DB::table('kyc_profile_fields')->insertGetId($values);
            $action = 'kyc.field.create';
        }

        $this->audit($action, 'kyc_fields', '保存 KYC 用户栏位 '.$id, [
            'id' => (int) $id,
            'keys' => array_keys($values),
        ]);

        return $this->success('用户信息栏位已保存', ['id' => (int) $id]);
    }

    public function deleteField($id)
    {
        $record = DB::table('kyc_profile_fields')->where('id', $id)->first();
        if (!$record) {
            return $this->error('栏位不存在或已删除', 404);
        }
        if ($record->is_system) {
            return $this->error('系统预设栏位不可删除，可关闭显示或 KYC 开关', 422);
        }

        DB::table('kyc_profile_fields')->where('id', $id)->delete();
        $this->audit('kyc.field.delete', 'kyc_fields', '删除 KYC 用户栏位 '.$record->field_key, [
            'id' => (int) $id,
            'field_key' => $record->field_key,
        ]);

        return $this->success('自订栏位已删除');
    }

    public function saveRule(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'review_mode' => 'required|in:manual,automatic',
            'image_count' => 'required|integer|min:1|max:6',
            'position' => 'nullable|integer|min:0|max:10000',
        ]);
        $record = $id
            ? DB::table('kyc_rule_groups')->where('id', $id)->first()
            : null;
        if ($id && !$record) {
            return $this->error('KYC 规则组不存在', 404);
        }

        $values = $this->service->filterRule($request->all());
        $values['image_titles'] = json_encode(
            $values['image_titles'] ?? [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $values['updated_by'] = $this->currentAdminId();
        $values['updated_at'] = now();

        if (!empty($values['is_default'])) {
            DB::table('kyc_rule_groups')->update(['is_default' => 0]);
        } elseif ($record && $record->is_default) {
            $values['is_default'] = 1;
        }

        if ($record) {
            DB::table('kyc_rule_groups')->where('id', $id)->update($values);
            $action = 'kyc.rule.update';
        } else {
            $values = array_merge([
                'is_default' => 0,
                'enabled' => 0,
                'review_mode' => 'manual',
                'force_enabled' => 0,
                'tag_internal' => 0,
                'tag_operation' => 0,
                'scenario_login' => 0,
                'scenario_deposit' => 0,
                'scenario_withdraw' => 0,
                'scenario_game' => 0,
                'require_id_type' => 0,
                'require_id_number' => 0,
                'require_withdraw_name' => 0,
                'require_document_images' => 1,
                'image_count' => 6,
                'image_titles' => json_encode(
                    ['front', 'back', 'third', 'fourth', 'fifth', 'sixth'],
                    JSON_UNESCAPED_UNICODE
                ),
                'position' => 0,
            ], $values);
            $values['created_at'] = now();
            $id = DB::table('kyc_rule_groups')->insertGetId($values);
            $action = 'kyc.rule.create';
        }

        $this->audit($action, 'kyc_rules', '保存 KYC 规则组 '.$id, [
            'id' => (int) $id,
            'name' => $values['name'],
        ]);

        return $this->success('KYC 功能配置已保存', ['id' => (int) $id]);
    }

    public function deleteRule($id)
    {
        $record = DB::table('kyc_rule_groups')->where('id', $id)->first();
        if (!$record) {
            return $this->error('KYC 规则组不存在', 404);
        }
        if ($record->is_default) {
            return $this->error('默认规则组不可删除', 422);
        }

        DB::table('kyc_rule_groups')->where('id', $id)->delete();
        $this->audit('kyc.rule.delete', 'kyc_rules', '删除 KYC 规则组 '.$record->name, [
            'id' => (int) $id,
        ]);

        return $this->success('KYC 规则组已删除');
    }

    public function saveContent(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:mobile,web,app',
            'language' => 'required|string|max:10',
            'step' => 'required|integer|min:1|max:4',
            'title' => 'nullable|string|max:191',
            'body' => 'nullable|string|max:20000',
            'button_text' => 'nullable|string|max:191',
            'secondary_button_text' => 'nullable|string|max:191',
            'background_image' => 'nullable|string|max:1000',
        ]);

        $values = $this->service->filterContent($request->all());
        $keys = [
            'platform' => $values['platform'],
            'language' => $values['language'],
            'step' => $values['step'],
        ];
        $values['updated_by'] = $this->currentAdminId();
        $values['updated_at'] = now();
        $values['created_at'] = now();
        DB::table('kyc_frontend_contents')->updateOrInsert($keys, $values);

        $this->audit('kyc.content.update', 'kyc_content', '保存 KYC 前台内容', $keys);

        return $this->success('KYC 前台内容已保存', $keys);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return $this->error('图片上传失败', 422);
        }

        $disk = config('admin.upload.disk') ?: 'public';
        $path = Storage::disk($disk)->putFile('kyc-content', $file);
        $url = $path;
        try {
            $url = Storage::disk($disk)->url($path);
        } catch (\Throwable $exception) {
            // Some inherited upload disks do not expose a public URL method.
        }

        $this->audit('kyc.content.upload', 'kyc_content', '上传 KYC 前台图片', [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);

        return $this->success('图片已上传', ['path' => $path, 'url' => $url]);
    }

    private function render(Content $content, Request $request, $code)
    {
        $page = $this->service->page($code);
        $module = $page['module'];
        $fields = collect();
        $rules = collect();
        $selectedRule = null;
        $contents = collect();
        $selectedContent = null;
        $platform = (string) $request->input('platform', 'mobile');
        $language = strtoupper((string) $request->input('language', 'EN'));
        $step = max(1, min(4, (int) $request->input('step', 1)));

        if ($module === 'fields') {
            $fields = DB::table('kyc_profile_fields')
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(function ($field) {
                    $field->option_list = json_decode((string) $field->options, true) ?: [];
                    return $field;
                });
        } elseif ($module === 'rules') {
            $rules = DB::table('kyc_rule_groups')
                ->orderByDesc('is_default')
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->map(function ($rule) {
                    $rule->image_title_list = json_decode((string) $rule->image_titles, true) ?: [];
                    return $rule;
                });
            $groupId = (int) $request->input('group', 0);
            $selectedRule = $rules->first(function ($rule) use ($groupId) {
                return $groupId > 0 && (int) $rule->id === $groupId;
            });
            if (!$selectedRule) {
                $selectedRule = $rules->first();
            }
        } else {
            if (!in_array($platform, ['mobile', 'web', 'app'], true)) {
                $platform = 'mobile';
            }
            $contents = DB::table('kyc_frontend_contents')
                ->orderBy('platform')
                ->orderBy('language')
                ->orderBy('step')
                ->get();
            $selectedContent = $contents->first(function ($item) use ($platform, $language, $step) {
                return $item->platform === $platform
                    && strtoupper($item->language) === $language
                    && (int) $item->step === $step;
            });
        }

        return $content
            ->title($page['title'])
            ->description('KYC身份管理 / '.$code)
            ->body(view('admin.kyc-settings', compact(
                'code',
                'page',
                'module',
                'fields',
                'rules',
                'selectedRule',
                'contents',
                'selectedContent',
                'platform',
                'language',
                'step'
            ))->render());
    }

    private function audit($action, $module, $content, array $context)
    {
        $admin = Admin::user();
        DB::table('admin_audit_logs')->insert([
            'admin_user_id' => $admin ? $admin->getKey() : null,
            'admin_name' => $admin ? $admin->username : null,
            'action' => $action,
            'module' => $module,
            'content' => $content,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 2000),
            'context' => json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'created_at' => now(),
        ]);
    }

    private function currentAdminId()
    {
        $admin = Admin::user();

        return $admin ? (int) $admin->getKey() : null;
    }

    private function success($message, array $data = [])
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function error($message, $status)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
