<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityApply;
use App\Models\ActivityType;
use App\Services\PromotionService;
use App\User as FrontUser;
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
            ->orderBy(
                Schema::hasColumn('activity_types', 'sort_order') ? 'sort_order' : 'id',
                Schema::hasColumn('activity_types', 'sort_order') ? 'desc' : 'asc'
            )
            ->orderBy('id', 'asc')
            ->get();

        $items = [[
            'id' => 0,
            'name' => "\u{5168}\u{90e8}",
            'icon' => '',
            'sort_order' => 9999,
        ]];

        foreach ($rows as $row) {
            $publicName = $this->activityTypePublicName($row);
            $items[] = [
                'id' => (int) $row->id,
                'name' => $publicName,
                'admin_name' => $this->cleanText($row->name ?? '', "\u{6d3b}\u{52a8}"),
                'enname' => $this->cleanText($row->enname ?? '', ''),
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
            return $this->returnMsg(404, null, 'Promotion not found');
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

    public function apply(Request $request, $id)
    {
        $activity = Activity::where('id', (int) $id)->first();
        if (!$activity) {
            return $this->returnMsg(404, null, 'Promotion not found');
        }

        $channel = $this->channel($request);
        if (empty($this->service->visible([$activity], $channel))) {
            return $this->returnMsg(202, null, 'Promotion is not available');
        }
        if ((int) ($activity->can_apply ?? 0) !== 1) {
            return $this->returnMsg(202, null, 'Promotion can not apply');
        }

        $user = $this->requestUser($request);
        if (!$user) {
            return $this->returnMsg(401, null, 'login expired');
        }

        if ($hit = $this->activityBlacklistHit($user, (int) $activity->id)) {
            return $this->returnMsg(202, null, $this->activityBlacklistMessage($hit));
        }

        $couponCheck = $this->validateActivityCouponForApply($request, $user, (int) $activity->id);
        if (!$couponCheck['ok']) {
            return $this->returnMsg(202, null, $couponCheck['message']);
        }

        $exists = ActivityApply::where('user_id', $user->id)
            ->where('activity_id', (int) $activity->id)
            ->first();
        if ($exists) {
            return $this->returnMsg(202, null, 'Promotion application already submitted');
        }

        try {
            DB::transaction(function () use ($activity, $user, $couponCheck) {
                ActivityApply::create([
                    'activity_id' => (int) $activity->id,
                    'user_id' => (int) $user->id,
                    'state' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                if (!$this->markActivityCouponUsed($couponCheck['coupon'], $user)) {
                    throw new \RuntimeException('activity coupon consume failed');
                }
            });
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate') !== false || stripos($e->getMessage(), 'activity_apply_activity_id_user_id_unique') !== false) {
                return $this->returnMsg(202, null, 'Promotion application already submitted');
            }

            \Illuminate\Support\Facades\Log::error('promotion apply failed', [
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'message' => $e->getMessage(),
            ]);

            return $this->returnMsg(500, null, 'Promotion apply failed');
        }

        return $this->returnMsg(200, ['applied' => true], 'Application submitted');
    }

    public function recordExposure(Request $request, $id)
    {
        if (Schema::hasTable('promotion_exposures')) {
            DB::table('promotion_exposures')->insert([
                'activity_id' => (int) $id,
                'user_id' => $this->requestUserId($request),
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
        $banner = $channel === 'mobile'
            ? ($activity->app_img ?: $activity->banner)
            : ($activity->banner ?: $activity->app_img);
        $popupImage = $channel === 'mobile'
            ? (($activity->app_popup_image ?? '') ?: ($activity->popup_image ?? '') ?: $banner)
            : (($activity->popup_image ?? '') ?: ($activity->app_popup_image ?? '') ?: $banner);
        $detailImage = $channel === 'mobile'
            ? (($activity->app_detail_image ?? '') ?: ($activity->detail_image ?? ''))
            : (($activity->detail_image ?? '') ?: ($activity->app_detail_image ?? ''));

        $row = [
            'id' => (int) $activity->id,
            'type' => (int) $activity->type,
            'type_name' => $this->activityTypePublicName($activity->type_data),
            'title' => $this->cleanText($this->displayText($activity->entitle ?? '', $activity->title ?? ''), "TH2.VIP \u{6d3b}\u{52a8}"),
            'banner' => $this->uploadUrl($banner),
            'app_img' => $this->uploadUrl($activity->app_img ?? ''),
            'popup_image' => $this->uploadUrl($popupImage),
            'app_popup_image' => $this->uploadUrl($activity->app_popup_image ?? ''),
            'detail_image' => $this->uploadUrl($detailImage),
            'app_detail_image' => $this->uploadUrl($activity->app_detail_image ?? ''),
            'button_text' => $this->buttonText($activity),
            'can_apply' => (int) ($activity->can_apply ?? 0),
            'requires_auth' => (int) ($activity->requires_auth ?? 0),
            'action_url' => (string) ($activity->action_url ?? ''),
            'is_popup' => (int) ($activity->is_popup ?? 0),
            'popup_frequency' => (string) (($activity->popup_frequency ?? '') ?: 'once'),
            'popup_delay_seconds' => (int) ($activity->popup_delay_seconds ?? 0),
            'sort_order' => (int) ($activity->sort_order ?? 0),
            'starts_at' => $activity->starts_at ? (string) $activity->starts_at : '',
            'ends_at' => $activity->ends_at ? (string) $activity->ends_at : '',
        ];

        if ($full) {
            $row['content'] = $this->cleanRichText(
                $this->displayText($activity->encontent ?? '', $activity->content ?? ''), "<p>\u{9009}\u{62e9}\u{8981}\u{53c2}\u{52a0}\u{7684}\u{6d3b}\u{52a8}\uff0c\u{5e76}\u{6309}\u{9875}\u{9762}\u{89c4}\u{5219}\u{63d0}\u{4ea4}\u{7533}\u{8bf7}\uff0c\u{7cfb}\u{7edf}\u{4f1a}\u{4ea4}\u{7ed9}\u{5ba2}\u{670d}\u{5ba1}\u{6838}\u{3002}</p>"
            );
            $row['memo'] = $this->cleanRichText(
                $this->displayText($activity->enmemo ?? '', $activity->memo ?? ''), "<p>\u{7533}\u{8bf7}\u{524d}\u{8bf7}\u{9605}\u{8bfb}\u{6d3b}\u{52a8}\u{89c4}\u{5219}\uff0c\u{5982}\u{6709}\u{7591}\u{95ee}\u{8bf7}\u{8054}\u{7cfb}\u{5ba2}\u{670d}\u{3002}</p>"
            );
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

    protected function activityTypePublicName($type)
    {
        if (!$type) {
            return "\u{6d3b}\u{52a8}";
        }

        return $this->cleanText(
            $this->displayText($type->enname ?? '', $type->name ?? ''),
            "\u{6d3b}\u{52a8}"
        );
    }

    protected function buttonText(Activity $activity)
    {
        if (Schema::hasColumn('activities', 'button_text')) {
            $configured = $this->cleanText($activity->button_text ?? '', '');
            if ($configured !== '') {
                return $configured;
            }
        }

        $url = trim((string) ($activity->action_url ?? ''));
        if ($url !== '') {
            if (stripos($url, 'recharge') !== false || stripos($url, 'deposit') !== false) {
                return "\u{7acb}\u{5373}\u{5145}\u{503c}";
            }
            if (stripos($url, 'support') !== false || stripos($url, 'service') !== false) {
                return "\u{8054}\u{7cfb}\u{5ba2}\u{670d}";
            }

            return "\u{67e5}\u{770b}\u{8be6}\u{60c5}";
        }

        return (int) ($activity->can_apply ?? 0) === 1 ? "\u{7533}\u{8bf7}\u{6d3b}\u{52a8}" : "\u{67e5}\u{770b}\u{8be6}\u{60c5}";
    }

    protected function cleanText($value, $fallback)
    {
        $value = trim(strip_tags((string) $value));
        if ($value === '' || $this->hasBrokenText($value)) {
            return $fallback;
        }

        return $value;
    }

    protected function cleanRichText($value, $fallback)
    {
        $value = trim((string) $value);
        if ($value === '' || $this->hasBrokenText($value)) {
            return $fallback;
        }

        return $value;
    }

    protected function hasBrokenText($value)
    {
        return preg_match('/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F000}-\x{F8FF}\x{FFFD}]/u', (string) $value) === 1;
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

    protected function requestUser(Request $request)
    {
        $cached = $request->attributes->get('api_auth_user');
        if ($cached) {
            return $cached;
        }

        $token = $request->header('Authorization', $request->header('authorization', ''));
        $token = trim(preg_replace('/^Bearer\s+/i', '', (string) $token));
        if ($token === '') {
            return null;
        }

        return FrontUser::where('api_token', $token)->first();
    }

    protected function requestUserId(Request $request)
    {
        $user = $this->requestUser($request);

        return $user ? (int) $user->id : null;
    }
}
