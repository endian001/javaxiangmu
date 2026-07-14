<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PromotionPixelEventService
{
    public function recordFromRequest(Request $request)
    {
        if (!Schema::hasTable('promotion_event_records')) {
            return false;
        }

        try {
            $payload = $request->all();
            $tracking = $this->trackingPayload($payload);
            $user = $this->requestUser($request, $payload);
            $event = $this->cleanText($payload['event'] ?? 'custom', 100);
            $amount = is_numeric($payload['amount'] ?? null) ? (float) $payload['amount'] : 0;

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
                'raw_record' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('promotion pixel event record failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function recordDepositArrival($recharge, array $context = [])
    {
        if (!Schema::hasTable('promotion_event_records') || !$recharge || empty($recharge->user_id)) {
            return false;
        }

        try {
            $user = User::where('id', $recharge->user_id)->first();
            $orderNo = (string) ($recharge->out_trade_no ?? $recharge->order_no ?? $recharge->id);
            if ($orderNo !== '' && DB::table('promotion_event_records')
                ->where('event', 'deposit')
                ->where('user_id', $recharge->user_id)
                ->where('raw_record', 'like', '%"order_no":"'.$this->escapeLike($orderNo).'"%')
                ->exists()) {
                return true;
            }

            $last = DB::table('promotion_event_records')
                ->where('user_id', $recharge->user_id)
                ->where(function ($query) {
                    $query->whereNotNull('facebook_pixel_id')
                        ->orWhereNotNull('tiktok_pixel_id')
                        ->orWhereNotNull('agent_account');
                })
                ->orderByDesc('id')
                ->first();

            DB::table('promotion_event_records')->insert([
                'link_id' => $last ? $last->link_id : null,
                'facebook_pixel_id' => $last ? $last->facebook_pixel_id : null,
                'tiktok_pixel_id' => $last ? $last->tiktok_pixel_id : null,
                'from_facebook' => $last ? (bool) $last->from_facebook : false,
                'registered_at' => $last ? $last->registered_at : null,
                'registration_url' => $last ? $last->registration_url : null,
                'agent_account' => $last ? $last->agent_account : null,
                'user_id' => (int) $recharge->user_id,
                'username' => $user ? $this->cleanText($user->username, 191) : null,
                'event' => 'deposit',
                'event_at' => now(),
                'amount' => is_numeric($recharge->amount ?? null) ? (float) $recharge->amount : 0,
                'url' => null,
                'user_agent' => $this->cleanText($context['source'] ?? 'server', 2000),
                'raw_record' => json_encode([
                    'event' => 'deposit',
                    'order_no' => $orderNo,
                    'recharge_id' => $recharge->id ?? null,
                    'source' => $context['source'] ?? 'server',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('promotion deposit pixel event record failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    private function requestUser(Request $request, array $payload)
    {
        $token = (string) $request->header('Authorization', '');
        $token = trim(preg_replace('/^Bearer\s+/i', '', $token));
        if ($token !== '') {
            $user = User::where('api_token', $token)->first();
            if ($user) {
                return $user;
            }
        }

        $username = $payload['username'] ?? ($payload['payload']['username'] ?? null);
        $username = trim((string) $username);

        return $username === '' ? null : User::where('username', $username)->first();
    }

    private function trackingPayload(array $payload)
    {
        $tracking = [];
        foreach (['tracking', 'params'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $tracking = array_merge($tracking, $payload[$key]);
            }
        }

        foreach ($payload as $key => $value) {
            if (is_scalar($value)) {
                $tracking[$key] = $value;
            }
        }

        return $tracking;
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

    private function cleanText($value, $limit)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
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
