<?php

namespace App\Admin\Controllers;

use App\Admin\Services\PromotionChannelService;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PromotionChannelController extends Controller
{
    private $service;

    public function __construct(PromotionChannelService $service)
    {
        $this->service = $service;
    }

    public function links(Content $content, Request $request)
    {
        return $this->index($content, $request, '280000');
    }

    public function domains(Content $content, Request $request)
    {
        return $this->index($content, $request, '21160');
    }

    public function landing(Content $content, Request $request)
    {
        return $this->index($content, $request, '280004');
    }

    public function seo(Content $content, Request $request)
    {
        return $this->index($content, $request, '280008');
    }

    public function push(Content $content, Request $request)
    {
        return $this->index($content, $request, '280015');
    }

    public function events(Content $content, Request $request)
    {
        return $this->index($content, $request, '280012');
    }

    public function index(Content $content, Request $request, $code)
    {
        $page = $this->service->page($code);
        $module = $page['module'];
        $items = null;
        $pushJobs = collect();
        $events = null;

        if ($module === 'events') {
            $events = $this->eventQuery($request)
                ->orderByDesc('id')
                ->paginate(30)
                ->appends($request->query());
        } else {
            $itemModule = $this->service->itemModule($code);
            $items = $this->itemQuery($itemModule, $request)
                ->orderBy('position')
                ->orderByDesc('id')
                ->paginate(30)
                ->appends($request->query());
            $items->getCollection()->transform(function ($item) {
                $item->meta = json_decode((string) $item->data, true) ?: [];

                return $item;
            });

            if ($module === 'push') {
                $pushJobs = DB::table('promotion_push_jobs as jobs')
                    ->leftJoin(
                        'promotion_channel_items as templates',
                        'templates.id',
                        '=',
                        'jobs.template_id'
                    )
                    ->select(['jobs.*', 'templates.name as template_name'])
                    ->orderByDesc('jobs.id')
                    ->limit(100)
                    ->get();
            }
        }

        $settings = DB::table('promotion_channel_settings')
            ->where('module', $module)
            ->pluck('setting_value', 'setting_key')
            ->all();
        $tab = (string) $request->input(
            'tab',
            $module === 'events' ? 'pixel-events' : ($module === 'push' ? 'settings' : 'list')
        );

        return $content
            ->title($page['title'])
            ->description('推广渠道 / '.$code)
            ->body(view('admin.promotion-channel', compact(
                'code',
                'page',
                'module',
                'items',
                'settings',
                'pushJobs',
                'events',
                'tab'
            ))->render());
    }

    public function saveItem(Request $request, $code, $id = null)
    {
        try {
            $module = $this->service->itemModule($code);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $rules = [
            'item_type' => 'nullable|string|max:60',
            'name' => 'nullable|string|max:191',
            'domain' => 'nullable|string|max:191',
            'owner' => 'nullable|string|max:191',
            'target' => 'nullable|string|max:1000',
            'position' => 'nullable|integer|min:0|max:100000',
        ];
        if (in_array((string) $code, ['280000', '21160'], true)) {
            $rules['domain'] = 'required|string|max:191';
        } else {
            $rules['name'] = 'required|string|max:191';
        }
        $data = $request->validate($rules);
        $now = now();
        $values = [
            'module' => $module,
            'item_type' => $this->nullableText($data['item_type'] ?? null),
            'name' => $this->nullableText($data['name'] ?? null),
            'domain' => $this->nullableText($data['domain'] ?? null),
            'owner' => $this->nullableText($data['owner'] ?? null),
            'target' => $this->nullableText($data['target'] ?? null),
            'status' => $this->asBoolean($request->input('status', 0)),
            'position' => (int) ($data['position'] ?? 0),
            'data' => json_encode(
                $this->service->filterItemData($code, $request->all()),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'updated_by' => $this->currentAdminId(),
            'updated_at' => $now,
        ];

        if ($id) {
            $record = DB::table('promotion_channel_items')
                ->where('id', $id)
                ->where('module', $module)
                ->first();
            if (!$record) {
                return $this->error('资料不存在或已删除', 404);
            }
            DB::table('promotion_channel_items')->where('id', $id)->update($values);
            $action = 'promotion.item.update';
        } else {
            $values['created_by'] = $this->currentAdminId();
            $values['created_at'] = $now;
            $id = DB::table('promotion_channel_items')->insertGetId($values);
            $action = 'promotion.item.create';
        }

        $this->audit($action, $module, '保存推广渠道资料 '.$id, [
            'code' => (string) $code,
            'id' => (int) $id,
        ]);

        return $this->success('资料已保存', ['id' => (int) $id]);
    }

    public function deleteItem($code, $id)
    {
        try {
            $module = $this->service->itemModule($code);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $record = DB::table('promotion_channel_items')
            ->where('id', $id)
            ->where('module', $module)
            ->first();
        if (!$record) {
            return $this->error('资料不存在或已删除', 404);
        }

        DB::table('promotion_channel_items')->where('id', $id)->delete();
        $this->audit('promotion.item.delete', $module, '删除推广渠道资料 '.$id, [
            'code' => (string) $code,
            'id' => (int) $id,
        ]);

        return $this->success('资料已删除');
    }

    public function bulkDelete(Request $request, $code)
    {
        try {
            $module = $this->service->itemModule($code);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $ids = array_values(array_unique(array_filter(array_map(
            'intval',
            (array) $request->input('ids', [])
        ))));
        $ids = array_slice($ids, 0, 100);
        if (!$ids) {
            return $this->error('请先选择要删除的资料', 422);
        }

        $deleted = DB::table('promotion_channel_items')
            ->where('module', $module)
            ->whereIn('id', $ids)
            ->delete();
        $this->audit('promotion.item.bulk_delete', $module, '批量删除推广渠道资料', [
            'code' => (string) $code,
            'ids' => $ids,
            'deleted' => $deleted,
        ]);

        return $this->success('已删除 '.$deleted.' 条资料', ['deleted' => $deleted]);
    }

    public function saveSettings(Request $request, $code)
    {
        try {
            $module = $this->service->module($code);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $input = $request->input('settings', $request->all());
        $settings = $this->service->filterSettings($code, is_array($input) ? $input : []);
        if (!$settings) {
            return $this->error('没有可保存的设置', 422);
        }

        DB::transaction(function () use ($module, $settings) {
            foreach ($settings as $key => $value) {
                DB::table('promotion_channel_settings')->updateOrInsert(
                    ['module' => $module, 'setting_key' => $key],
                    [
                        'setting_value' => $value,
                        'updated_by' => $this->currentAdminId(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        $updated = 0;
        if (
            (string) $code === '21160'
            && ($settings['operation'] ?? '') === 'replace_target'
            && trim($settings['old_target'] ?? '') !== ''
            && trim($settings['new_target'] ?? '') !== ''
        ) {
            $updated = DB::table('promotion_channel_items')
                ->where('module', 'domains')
                ->where('target', $settings['old_target'])
                ->update([
                    'target' => $settings['new_target'],
                    'updated_by' => $this->currentAdminId(),
                    'updated_at' => now(),
                ]);
        }

        $this->audit('promotion.settings.update', $module, '更新推广渠道设置', [
            'code' => (string) $code,
            'keys' => array_keys($settings),
            'updated_items' => $updated,
        ]);

        return $this->success('设置已保存', ['updated_items' => $updated]);
    }

    public function createPushJob(Request $request, $code)
    {
        if ((string) $code !== '280015') {
            return $this->error('当前页面不支持推播任务', 404);
        }

        $data = $request->validate([
            'template_id' => 'nullable|integer|min:1',
            'title' => 'nullable|string|max:191',
            'content' => 'nullable|string|max:5000',
            'audience_type' => 'required|in:all,never,today,installed_3_days,companion_days',
            'audience_value' => 'nullable|string|max:191',
            'send_mode' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'nullable|date',
        ]);
        $title = trim((string) ($data['title'] ?? ''));
        $body = trim((string) ($data['content'] ?? ''));
        $templateId = isset($data['template_id']) ? (int) $data['template_id'] : null;

        if ($templateId) {
            $template = DB::table('promotion_channel_items')
                ->where('id', $templateId)
                ->where('module', 'push-template')
                ->first();
            if (!$template) {
                return $this->error('推播模板不存在', 422);
            }
            $meta = json_decode((string) $template->data, true) ?: [];
            $title = $title !== '' ? $title : (string) ($meta['push_title'] ?? $template->name);
            $body = $body !== '' ? $body : (string) ($meta['push_content'] ?? '');
        }

        if ($title === '' || $body === '') {
            return $this->error('推播标题和内容不能为空', 422);
        }

        $scheduledAt = null;
        if ($data['send_mode'] === 'scheduled') {
            if (empty($data['scheduled_at'])) {
                return $this->error('定时发送必须填写排程时间', 422);
            }
            $scheduledAt = date('Y-m-d H:i:s', strtotime($data['scheduled_at']));
        }

        $now = now();
        $id = DB::table('promotion_push_jobs')->insertGetId([
            'template_id' => $templateId,
            'title' => mb_substr($title, 0, 191),
            'content' => mb_substr($body, 0, 5000),
            'audience_type' => $data['audience_type'],
            'audience_value' => $this->nullableText($data['audience_value'] ?? null),
            'scheduled_at' => $scheduledAt,
            'status' => $data['send_mode'] === 'scheduled' ? 'scheduled' : 'queued',
            'total_count' => 0,
            'success_count' => 0,
            'requested_by' => $this->currentAdminId(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->audit('promotion.push.create', 'push', '建立推播任务 '.$id, [
            'id' => $id,
            'send_mode' => $data['send_mode'],
            'audience_type' => $data['audience_type'],
        ]);

        return $this->success('推播任务已进入队列', ['id' => $id]);
    }

    public function exportEvents(Request $request, $code)
    {
        if ((string) $code !== '280012') {
            abort(404);
        }

        $records = $this->eventQuery($request)->orderByDesc('id')->limit(10000)->get();
        $this->audit('promotion.events.export', 'events', '导出推广事件记录', [
            'count' => $records->count(),
        ]);

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID',
                '链接ID',
                '脸书像素ID',
                '抖音像素ID',
                '来自FB广告',
                '注册时间',
                '注册网址',
                '代理账号',
                '用户ID',
                '用户名',
                '事件',
                '活动时间',
                '金额',
                '网址',
                '用户代理',
                '原始记录',
            ]);
            foreach ($records as $record) {
                fputcsv($handle, [
                    $record->id,
                    $record->link_id,
                    $record->facebook_pixel_id,
                    $record->tiktok_pixel_id,
                    $record->from_facebook ? '是' : '否',
                    $record->registered_at,
                    $record->registration_url,
                    $record->agent_account,
                    $record->user_id,
                    $record->username,
                    $record->event,
                    $record->event_at,
                    $record->amount,
                    $record->url,
                    $record->user_agent,
                    $record->raw_record,
                ]);
            }
            fclose($handle);
        }, 'promotion-events-'.date('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function itemQuery($module, Request $request)
    {
        $query = DB::table('promotion_channel_items')->where('module', $module);
        $keyword = trim((string) $request->input('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $like = '%'.$keyword.'%';
                $inner->where('name', 'like', $like)
                    ->orWhere('domain', 'like', $like)
                    ->orWhere('owner', 'like', $like)
                    ->orWhere('target', 'like', $like)
                    ->orWhere('data', 'like', $like);
            });
        }
        foreach ([
            'type' => 'item_type',
            'owner' => 'owner',
            'domain' => 'domain',
            'target' => 'target',
        ] as $input => $column) {
            $value = trim((string) $request->input($input, ''));
            if ($value !== '') {
                $query->where($column, 'like', '%'.$value.'%');
            }
        }
        if ($request->input('status') !== null && $request->input('status') !== '') {
            $query->where('status', $this->asBoolean($request->input('status')));
        }

        return $query;
    }

    private function eventQuery(Request $request)
    {
        $query = DB::table('promotion_event_records');
        foreach ([
            'link_id' => 'link_id',
            'facebook_pixel_id' => 'facebook_pixel_id',
            'tiktok_pixel_id' => 'tiktok_pixel_id',
            'username' => 'username',
            'agent_account' => 'agent_account',
            'event' => 'event',
        ] as $input => $column) {
            $value = trim((string) $request->input($input, ''));
            if ($value !== '') {
                $query->where($column, 'like', '%'.$value.'%');
            }
        }
        if ($request->filled('start_at')) {
            $query->where('event_at', '>=', $request->input('start_at').' 00:00:00');
        }
        if ($request->filled('end_at')) {
            $query->where('event_at', '<=', $request->input('end_at').' 23:59:59');
        }
        $raw = trim((string) $request->input('raw_record', ''));
        if ($raw !== '') {
            $query->where('raw_record', 'like', '%'.$raw.'%');
        }

        return $query;
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
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }

    private function currentAdminId()
    {
        $admin = Admin::user();

        return $admin ? (int) $admin->getKey() : null;
    }

    private function nullableText($value)
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function asBoolean($value)
    {
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
    }

    private function success($message, array $data = [])
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data]);
    }

    private function error($message, $status)
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
