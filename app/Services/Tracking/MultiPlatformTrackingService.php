<?php

namespace App\Services\Tracking;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MultiPlatformTrackingService
{
    private $dispatcher;

    public function __construct(TrackingPostbackDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher ?: new TrackingPostbackDispatcher();
    }

    public function recordBrowserEvent(Request $request)
    {
        try {
            $payload = $request->all();
            $tracking = $this->trackingPayload($payload);
            $event = $this->cleanText($payload['event'] ?? 'custom', 100) ?: 'custom';
            $user = $this->requestUser($request, $payload, $event);
            $amount = is_numeric($payload['amount'] ?? null) ? (float) $payload['amount'] : 0;
            $currency = $this->cleanText($payload['currency'] ?? 'THB', 20) ?: 'THB';

            $attribution = $this->upsertAttribution($request, $payload, $tracking, $user, $event);
            if ($event === 'register' && $user && $attribution) {
                $this->bindAttributionToUser((int) $attribution['id'], $user, $payload);
                $attribution = $this->attributionById((int) $attribution['id']) ?: $attribution;
            }

            $conversion = $this->createConversionEvent($event, [
                'source' => 'browser',
                'attribution' => $attribution,
                'user' => $user,
                'amount' => $amount,
                'currency' => $currency,
                'payload' => $payload,
                'event_id' => $payload['event_id'] ?? null,
                'event_at' => now(),
            ]);

            if (($conversion['created'] ?? false) && !empty($conversion['record'])) {
                $this->dispatcher->dispatchForConversion($conversion['id'], $conversion['record'], $attribution ?: []);
            }

            $this->writeLegacyBrowserEventRecord($request, $payload, $tracking, $user, $event, $amount);

            return true;
        } catch (\Throwable $e) {
            Log::warning('multi platform browser tracking failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function recordRechargeArrival($recharge, array $context = [])
    {
        if (!$recharge || empty($recharge->user_id)) {
            return false;
        }

        try {
            $user = User::where('id', $recharge->user_id)->first();
            $attribution = $this->lastAttributionForUser((int) $recharge->user_id);
            $firstDeposit = $this->isFirstSuccessfulRecharge($recharge);
            $orderNo = $this->orderNo($recharge);
            $amount = is_numeric($recharge->amount ?? null) ? (float) $recharge->amount : 0;
            $events = $firstDeposit
                ? ['firstDepositArrival', 'startTrial', 'deposit']
                : ['redeposit', 'deposit'];

            foreach ($events as $event) {
                $payload = [
                    'event' => $event,
                    'order_no' => $orderNo,
                    'recharge_id' => $recharge->id ?? null,
                    'source' => $context['source'] ?? 'server',
                    'is_first_deposit' => $firstDeposit,
                ];
                $conversion = $this->createConversionEvent($event, [
                    'source' => $context['source'] ?? 'server',
                    'attribution' => $attribution,
                    'user' => $user,
                    'amount' => $amount,
                    'currency' => $context['currency'] ?? 'THB',
                    'payload' => $payload,
                    'recharge_id' => $recharge->id ?? null,
                    'order_no' => $orderNo,
                    'is_first_deposit' => $firstDeposit,
                    'event_at' => now(),
                ]);

                if (($conversion['created'] ?? false) && !empty($conversion['record'])) {
                    $this->dispatcher->dispatchForConversion($conversion['id'], $conversion['record'], $attribution ?: []);
                }

                $this->writeLegacyRechargeEventRecord($recharge, $user, $attribution, $event, $payload, $context);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('multi platform recharge tracking failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function upsertAttribution(Request $request, array $payload, array $tracking, $user, string $event): array
    {
        if (!Schema::hasTable('promotion_tracking_attributions')) {
            return [];
        }

        $browserId = $this->cleanText($payload['browser_id'] ?? ($tracking['browser_id'] ?? null), 100);
        $sessionId = $this->cleanText($payload['session_id'] ?? ($tracking['session_id'] ?? null), 100);
        $key = $this->attributionKey($browserId, $tracking, $request);
        $now = now();
        $existing = DB::table('promotion_tracking_attributions')->where('attribution_key', $key)->first();
        $clickIds = TrackingPlatformCatalog::clickIds($tracking);
        $platforms = TrackingPlatformCatalog::platformForTracking($tracking);
        $incomingUserId = $user ? (int) $user->id : $this->intOrNull($payload['user_id'] ?? null);
        $incomingUsername = $user
            ? $this->cleanText($user->username, 191)
            : $this->cleanText($payload['username'] ?? ($payload['payload']['username'] ?? null), 191);

        if ($existing && $this->attributionSubjectChanged($existing, $incomingUserId, $incomingUsername, $event, $payload)) {
            $subject = $incomingUsername ?: ($incomingUserId ? 'user:'.$incomingUserId : 'session:'.($sessionId ?: sha1($request->ip().$request->userAgent().json_encode($tracking))));
            $key = $this->attributionKey($browserId, $tracking, $request, $subject);
            $existing = DB::table('promotion_tracking_attributions')->where('attribution_key', $key)->first();
        }

        $values = [
            'browser_id' => $browserId,
            'session_id' => $sessionId,
            'user_id' => $incomingUserId,
            'username' => $incomingUsername,
            'agent_account' => $this->agentAccount($tracking),
            'link_id' => $this->intOrNull($tracking['linkId'] ?? null),
            'landing_url' => $this->cleanText($payload['url'] ?? null, 1000),
            'registration_url' => in_array($event, ['registerSubmit', 'register'], true) ? $this->cleanText($payload['url'] ?? null, 1000) : null,
            'referrer' => $this->cleanText($payload['referrer'] ?? $request->header('referer'), 1000),
            'ip_address' => $this->cleanText($request->ip(), 64),
            'user_agent' => $this->cleanText($request->userAgent(), 2000),
            'params_json' => $this->json($tracking),
            'click_ids_json' => $this->json($clickIds),
            'platforms_json' => $this->json($platforms),
            'registered_at' => $event === 'register' ? $now : null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $values['landing_url'] = $existing->landing_url ?: $values['landing_url'];
            $values['first_event_at'] = $existing->first_event_at ?: $now;
            $values['registered_at'] = $existing->registered_at ?: $values['registered_at'];
            $values['user_id'] = $values['user_id'] ?: $existing->user_id;
            $values['username'] = $values['username'] ?: $existing->username;
            DB::table('promotion_tracking_attributions')->where('id', $existing->id)->update($values);

            return $this->attributionById((int) $existing->id) ?: [];
        }

        $values['attribution_key'] = $key;
        $values['first_event_at'] = $now;
        $values['created_at'] = $now;
        $id = DB::table('promotion_tracking_attributions')->insertGetId($values);

        return $this->attributionById((int) $id) ?: [];
    }

    public function bindAttributionToUser(int $attributionId, User $user, array $payload = []): void
    {
        if (!Schema::hasTable('promotion_tracking_attributions')) {
            return;
        }

        DB::table('promotion_tracking_attributions')->where('id', $attributionId)->update([
            'user_id' => (int) $user->id,
            'username' => $this->cleanText($user->username, 191),
            'registration_url' => $this->cleanText($payload['url'] ?? null, 1000),
            'registered_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createConversionEvent(string $event, array $context): array
    {
        if (!Schema::hasTable('promotion_tracking_conversions')) {
            return ['created' => false, 'id' => null, 'record' => []];
        }

        $attribution = $context['attribution'] ?? [];
        $user = $context['user'] ?? null;
        $payload = $context['payload'] ?? [];
        $eventId = $this->cleanText($context['event_id'] ?? null, 191)
            ?: $this->stableEventId($event, $context);
        $existing = DB::table('promotion_tracking_conversions')->where('event_id', $eventId)->first();
        if ($existing) {
            return ['created' => false, 'id' => (int) $existing->id, 'record' => $this->recordToArray($existing)];
        }

        $values = [
            'event_id' => $eventId,
            'attribution_id' => $attribution['id'] ?? null,
            'event_name' => $this->cleanText($event, 100),
            'standard_event' => TrackingPlatformCatalog::standardEvent($event),
            'source' => $this->cleanText($context['source'] ?? 'server', 60),
            'user_id' => $user ? (int) $user->id : ($attribution['user_id'] ?? null),
            'username' => $user ? $this->cleanText($user->username, 191) : ($attribution['username'] ?? null),
            'recharge_id' => $this->intOrNull($context['recharge_id'] ?? null),
            'order_no' => $this->cleanText($context['order_no'] ?? ($payload['order_no'] ?? null), 191),
            'amount' => is_numeric($context['amount'] ?? null) ? (float) $context['amount'] : 0,
            'currency' => $this->cleanText($context['currency'] ?? 'THB', 20) ?: 'THB',
            'is_first_deposit' => !empty($context['is_first_deposit']),
            'event_at' => $context['event_at'] ?? now(),
            'payload_json' => $this->json($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('promotion_tracking_conversions')->insertGetId($values);
        $values['id'] = $id;

        return ['created' => true, 'id' => $id, 'record' => $values];
    }

    public function isFirstSuccessfulRecharge($recharge): bool
    {
        if (!Schema::hasTable('recharge') || empty($recharge->user_id)) {
            return false;
        }

        $query = DB::table('recharge')
            ->where('user_id', $recharge->user_id)
            ->where('state', 2);

        return $query->count() <= 1;
    }

    private function writeLegacyBrowserEventRecord(Request $request, array $payload, array $tracking, $user, string $event, float $amount): void
    {
        if (!Schema::hasTable('promotion_event_records')) {
            return;
        }

        DB::table('promotion_event_records')->insert([
            'link_id' => $this->intOrNull($tracking['linkId'] ?? null),
            'facebook_pixel_id' => $this->cleanText($tracking['fbPixelId'] ?? null, 191),
            'tiktok_pixel_id' => $this->cleanText($tracking['tiktokPixelId'] ?? null, 191),
            'from_facebook' => !empty($tracking['fbclid']) || !empty($tracking['fbPixelId']),
            'registered_at' => $event === 'register' ? now() : null,
            'registration_url' => in_array($event, ['registerSubmit', 'register'], true)
                ? $this->cleanText($payload['url'] ?? null, 1000)
                : null,
            'agent_account' => $this->agentAccount($tracking),
            'user_id' => $user ? (int) $user->id : $this->intOrNull($payload['user_id'] ?? null),
            'username' => $user ? $this->cleanText($user->username, 191) : $this->cleanText($payload['username'] ?? ($payload['payload']['username'] ?? null), 191),
            'event' => $event,
            'event_at' => now(),
            'amount' => $amount,
            'url' => $this->cleanText($payload['url'] ?? null, 1000),
            'user_agent' => $this->cleanText($request->userAgent(), 2000),
            'raw_record' => $this->json($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function writeLegacyRechargeEventRecord($recharge, $user, array $attribution, string $event, array $payload, array $context): void
    {
        if (!Schema::hasTable('promotion_event_records')) {
            return;
        }

        $orderNo = $this->orderNo($recharge);
        if ($orderNo !== '' && DB::table('promotion_event_records')
            ->where('event', $event)
            ->where('user_id', $recharge->user_id)
            ->where('raw_record', 'like', '%"order_no":"'.$this->escapeLike($orderNo).'"%')
            ->exists()) {
            return;
        }

        $legacy = $this->legacyAttributionForUser((int) $recharge->user_id);
        $tracking = $this->decodeJson($attribution['params_json'] ?? null);

        DB::table('promotion_event_records')->insert([
            'link_id' => $attribution['link_id'] ?? ($legacy ? $legacy->link_id : null),
            'facebook_pixel_id' => $tracking['fbPixelId'] ?? ($legacy ? $legacy->facebook_pixel_id : null),
            'tiktok_pixel_id' => $tracking['tiktokPixelId'] ?? ($legacy ? $legacy->tiktok_pixel_id : null),
            'from_facebook' => !empty($tracking['fbclid']) || !empty($tracking['fbPixelId']) || ($legacy ? (bool) $legacy->from_facebook : false),
            'registered_at' => $attribution['registered_at'] ?? ($legacy ? $legacy->registered_at : null),
            'registration_url' => $attribution['registration_url'] ?? ($legacy ? $legacy->registration_url : null),
            'agent_account' => $attribution['agent_account'] ?? ($legacy ? $legacy->agent_account : null),
            'user_id' => (int) $recharge->user_id,
            'username' => $user ? $this->cleanText($user->username, 191) : null,
            'event' => $event,
            'event_at' => now(),
            'amount' => is_numeric($recharge->amount ?? null) ? (float) $recharge->amount : 0,
            'url' => null,
            'user_agent' => $this->cleanText($context['source'] ?? 'server', 2000),
            'raw_record' => $this->json($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function lastAttributionForUser(int $userId): array
    {
        if (Schema::hasTable('promotion_tracking_attributions')) {
            $record = DB::table('promotion_tracking_attributions')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();
            if ($record) {
                return $this->recordToArray($record);
            }
        }

        $legacy = $this->legacyAttributionForUser($userId);
        if (!$legacy) {
            return [];
        }

        $raw = $this->decodeJson($legacy->raw_record ?? null);
        $tracking = $this->trackingPayload($raw);

        return [
            'id' => null,
            'user_id' => $legacy->user_id,
            'username' => $legacy->username,
            'agent_account' => $legacy->agent_account,
            'link_id' => $legacy->link_id,
            'landing_url' => $legacy->url,
            'registration_url' => $legacy->registration_url,
            'ip_address' => null,
            'user_agent' => $legacy->user_agent,
            'params_json' => $this->json($tracking),
            'registered_at' => $legacy->registered_at,
        ];
    }

    private function legacyAttributionForUser(int $userId)
    {
        if (!Schema::hasTable('promotion_event_records')) {
            return null;
        }

        return DB::table('promotion_event_records')
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNotNull('facebook_pixel_id')
                    ->orWhereNotNull('tiktok_pixel_id')
                    ->orWhereNotNull('agent_account')
                    ->orWhere('raw_record', 'like', '%tracking%');
            })
            ->orderByDesc('id')
            ->first();
    }

    private function attributionById(int $id): array
    {
        $record = DB::table('promotion_tracking_attributions')->where('id', $id)->first();

        return $record ? $this->recordToArray($record) : [];
    }

    private function requestUser(Request $request, array $payload, string $event = '')
    {
        $username = $payload['username'] ?? ($payload['payload']['username'] ?? null);
        $username = trim((string) $username);
        $url = (string) ($payload['url'] ?? '');

        if (in_array($event, ['registerSubmit', 'register'], true)) {
            return $username === '' ? null : User::where('username', $username)->first();
        }

        if ($event === 'firstOpen' && strpos($url, '/register') !== false) {
            return null;
        }

        $token = (string) $request->header('Authorization', '');
        $token = trim(preg_replace('/^Bearer\s+/i', '', $token));
        if ($token !== '') {
            $user = User::where('api_token', $token)->first();
            if ($user) {
                return $user;
            }
        }

        return $username === '' ? null : User::where('username', $username)->first();
    }

    private function attributionSubjectChanged($existing, $incomingUserId, $incomingUsername, string $event, array $payload): bool
    {
        if (!$existing || (empty($existing->user_id) && trim((string) $existing->username) === '')) {
            return false;
        }

        if ($incomingUserId && !empty($existing->user_id) && (int) $existing->user_id !== (int) $incomingUserId) {
            return true;
        }

        if ($incomingUsername && trim((string) $existing->username) !== '' && trim((string) $existing->username) !== $incomingUsername) {
            return true;
        }

        $url = (string) ($payload['url'] ?? '');
        return $event === 'firstOpen' && strpos($url, '/register') !== false && !empty($existing->user_id);
    }

    private function attributionKey($browserId, array $tracking, Request $request, string $subject = null): string
    {
        ksort($tracking);
        $trackingHash = sha1(json_encode($tracking, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $seed = ($browserId ?: ($request->ip().'|'.$request->userAgent())).'|'.$trackingHash;
        if ($subject !== null && $subject !== '') {
            $seed .= '|'.$subject;
        }

        return sha1($seed);
    }

    private function trackingPayload(array $payload): array
    {
        $tracking = [];
        foreach (['tracking', 'params'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $tracking = array_merge($tracking, $payload[$key]);
            }
        }

        foreach ($payload as $key => $value) {
            if (in_array($key, TrackingPlatformCatalog::captureKeys(), true) && is_scalar($value)) {
                $tracking[$key] = $value;
            }
        }

        return $this->sanitizeTracking($tracking);
    }

    private function sanitizeTracking(array $tracking): array
    {
        $allowed = array_flip(TrackingPlatformCatalog::captureKeys());
        $result = [];
        foreach ($tracking as $key => $value) {
            if (!isset($allowed[$key]) || !is_scalar($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $result[$key] = mb_substr($value, 0, 500);
            }
        }

        return $result;
    }

    private function agentAccount(array $tracking)
    {
        foreach (['affiliateCode', 'agentCode', 'invite_code', 'pid'] as $key) {
            if (!empty($tracking[$key])) {
                return $this->cleanText($tracking[$key], 191);
            }
        }

        return null;
    }

    private function stableEventId(string $event, array $context): string
    {
        $user = $context['user'] ?? null;
        $userId = $user ? (int) $user->id : ($context['attribution']['user_id'] ?? 0);
        $parts = [
            $event,
            $userId,
            $context['order_no'] ?? '',
            $context['recharge_id'] ?? '',
        ];

        return 'evt_'.sha1(implode('|', array_map('strval', $parts)));
    }

    private function orderNo($recharge): string
    {
        return (string) ($recharge->out_trade_no ?? $recharge->order_no ?? $recharge->id ?? '');
    }

    private function recordToArray($record): array
    {
        return json_decode(json_encode($record), true) ?: [];
    }

    private function decodeJson($value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function json($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function cleanText($value, int $limit)
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    private function intOrNull($value)
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function escapeLike($value)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
