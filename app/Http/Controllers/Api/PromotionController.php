<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PromotionController extends Controller
{
    protected $service;

    public function __construct(PromotionService $service)
    {
        $this->service = $service;
    }

    public function categories(Request $request)
    {
        $rows = ActivityType::query()
            ->when(Schema::hasColumn('activity_types', 'state'), function ($query) {
                return $query->where('state', 1);
            })
            ->orderBy(Schema::hasColumn('activity_types', 'sort_order') ? 'sort_order' : 'id', Schema::hasColumn('activity_types', 'sort_order') ? 'desc' : 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $items = [[
            'id' => 0,
            'name' => 'ทั้งหมด',
            'icon' => '',
            'sort_order' => 9999,
        ]];

        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?: ''),
                'icon' => $this->uploadUrl($row->icon ?? ''),
                'sort_order' => (int) ($row->sort_order ?? 0),
            ];
        }

        return $this->returnMsg(200, $items, 'success');
    }

    public function index(Request $request)
    {
        $channel = $this->channel($request);
        $type = (int) $request->input('type', 0);
        $items = Activity::with('type_data')
            ->when($type > 0, function ($query) use ($type) {
                return $query->where('type', $type);
            })
            ->get()
            ->all();

        $visible = $this->service->visible($items, $channel);
        $rows = [];
        foreach ($visible as $activity) {
            $rows[] = $this->formatActivity($activity, $channel, false);
        }

        return $this->returnMsg(200, [
            'data' => $rows,
            'total' => count($rows),
            'channel' => $channel,
        ], 'success');
    }

    public function show(Request $request, $id)
    {
        $channel = $this->channel($request);
        $activity = Activity::with('type_data')->where('id', (int) $id)->first();
        if (!$activity || empty($this->service->visible([$activity], $channel))) {
            return $this->returnMsg(404, null, 'ไม่พบโปรโมชั่น');
        }

        return $this->returnMsg(200, $this->formatActivity($activity, $channel, true), 'success');
    }

    public function popup(Request $request)
    {
        $channel = $this->channel($request);
        $popup = $this->service->popup(Activity::with('type_data')->get()->all(), $channel);
        if (!$popup) {
            return $this->returnMsg(200, null, 'success');
        }

        return $this->returnMsg(200, $this->formatActivity($popup, $channel, true), 'success');
    }

    public function recordExposure(Request $request, $id)
    {
        if (Schema::hasTable('promotion_exposures')) {
            DB::table('promotion_exposures')->insert([
                'activity_id' => (int) $id,
                'user_id' => null,
                'session_key' => substr((string) $request->input('session_key', ''), 0, 120),
                'channel' => $this->channel($request),
                'source' => substr((string) $request->input('source', 'promotion_center'), 0, 40),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->returnMsg(200, ['recorded' => true], 'success');
    }

    protected function formatActivity(Activity $activity, $channel, $full)
    {
        $banner = $channel === 'mobile' ? ($activity->app_img ?: $activity->banner) : ($activity->banner ?: $activity->app_img);
        $popupImage = $channel === 'mobile'
            ? (($activity->app_popup_image ?? '') ?: ($activity->popup_image ?? '') ?: $banner)
            : (($activity->popup_image ?? '') ?: ($activity->app_popup_image ?? '') ?: $banner);
        $detailImage = $channel === 'mobile'
            ? (($activity->app_detail_image ?? '') ?: ($activity->detail_image ?? ''))
            : (($activity->detail_image ?? '') ?: ($activity->app_detail_image ?? ''));

        $row = [
            'id' => (int) $activity->id,
            'type' => (int) $activity->type,
            'type_name' => optional($activity->type_data)->name ?: '',
            'title' => $this->displayText($activity->entitle ?? '', $activity->title ?? ''),
            'banner' => $this->uploadUrl($banner),
            'app_img' => $this->uploadUrl($activity->app_img ?? ''),
            'popup_image' => $this->uploadUrl($popupImage),
            'detail_image' => $this->uploadUrl($detailImage),
            'can_apply' => (int) ($activity->can_apply ?? 0),
            'requires_auth' => (int) ($activity->requires_auth ?? 0),
            'action_url' => (string) ($activity->action_url ?? ''),
            'is_popup' => (int) ($activity->is_popup ?? 0),
            'popup_frequency' => (string) (($activity->popup_frequency ?? '') ?: 'daily'),
            'popup_delay_seconds' => (int) ($activity->popup_delay_seconds ?? 0),
            'sort_order' => (int) ($activity->sort_order ?? 0),
            'starts_at' => $activity->starts_at ? (string) $activity->starts_at : '',
            'ends_at' => $activity->ends_at ? (string) $activity->ends_at : '',
        ];

        if ($full) {
            $row['content'] = $this->displayText($activity->encontent ?? '', $activity->content ?? '');
            $row['memo'] = $this->displayText($activity->enmemo ?? '', $activity->memo ?? '');
        }

        return $row;
    }

    protected function displayText($primary, $fallback)
    {
        $primary = trim((string) $primary);
        if ($primary !== '') {
            return $primary;
        }

        return trim((string) $fallback);
    }

    protected function channel(Request $request)
    {
        $channel = strtolower((string) $request->input('channel', ''));
        if (in_array($channel, ['mobile', 'app', 'h5'], true)) {
            return 'mobile';
        }
        if (in_array($channel, ['desktop', 'pc', 'web'], true)) {
            return 'desktop';
        }

        return preg_match('/Mobile|Android|iPhone|iPad|iPod/i', (string) $request->header('User-Agent'))
            ? 'mobile'
            : 'desktop';
    }

    protected function uploadUrl($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if (strpos($path, '/assets/') === 0) {
            return rtrim((string) env('APP_URL'), '/') . $path;
        }

        return rtrim((string) env('APP_URL'), '/') . '/uploads/' . ltrim($path, '/');
    }
}
