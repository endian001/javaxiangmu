<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TcgBusinessOperationService
{
    public function activityBlacklistHit($user, $activityId)
    {
        if (!Schema::hasTable('tcg_activity_blacklists')) {
            return null;
        }

        return DB::table('tcg_activity_blacklists')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $this->now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $this->now());
            })
            ->where(function ($query) use ($user) {
                $this->applyUserMatch($query, $user);
            })
            ->where(function ($query) use ($activityId) {
                $query->whereNull('activity_id')
                    ->orWhere('activity_id', $activityId);
            })
            ->orderByDesc('id')
            ->first();
    }

    public function activityBlacklistMessage($hit, $fallback = 'Activity application is restricted')
    {
        if (!$hit) {
            return $fallback;
        }

        foreach (['reason', 'remark'] as $field) {
            if (isset($hit->{$field}) && trim((string) $hit->{$field}) !== '') {
                return $fallback . ': ' . $hit->{$field};
            }
        }

        return $fallback;
    }

    public function couponForApply($couponCode, $user, $activityId)
    {
        $couponCode = trim((string) $couponCode);
        if ($couponCode === '' || !Schema::hasTable('tcg_activity_coupons')) {
            return null;
        }

        $query = DB::table('tcg_activity_coupons')
            ->where('coupon_code', $couponCode)
            ->whereIn('status', ['active', 'issued'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $this->now());
            })
            ->where(function ($query) use ($activityId) {
                $query->whereNull('activity_id')
                    ->orWhere('activity_id', $activityId);
            })
            ->where(function ($query) use ($user) {
                $query->whereNull('username')
                    ->orWhere('username', '')
                    ->orWhere('username', $this->username($user));
            });

        if (Schema::hasColumn('tcg_activity_coupons', 'user_id')) {
            $query->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', 0)
                    ->orWhere('user_id', '')
                    ->orWhere('user_id', $this->userId($user));
            });
        }

        return $query->orderByDesc('id')->first();
    }

    public function markCouponUsed($couponId, $user = null)
    {
        $updates = [
            'status' => 'used',
        ];

        if (Schema::hasColumn('tcg_activity_coupons', 'used_at')) {
            $updates['used_at'] = $this->now();
        }

        if ($user && Schema::hasColumn('tcg_activity_coupons', 'used_by')) {
            $updates['used_by'] = $this->userId($user);
        }

        if (Schema::hasColumn('tcg_activity_coupons', 'updated_at')) {
            $updates['updated_at'] = $this->now();
        }

        return DB::table('tcg_activity_coupons')
            ->where('id', $couponId)
            ->whereIn('status', ['active', 'issued'])
            ->update($updates) > 0;
    }

    public function multiplierRuleFor($activityId, $amount = 0)
    {
        if (!Schema::hasTable('tcg_activity_multiplier_rules')) {
            return null;
        }

        $amount = (float) $amount;

        return DB::table('tcg_activity_multiplier_rules')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $this->now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $this->now());
            })
            ->where(function ($query) use ($activityId) {
                $query->whereNull('activity_id')
                    ->orWhere('activity_id', (int) $activityId);
            })
            ->where(function ($query) use ($amount) {
                $query->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', $amount);
            })
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->orderByDesc('activity_id')
            ->orderByDesc('multiplier')
            ->orderByDesc('id')
            ->first();
    }

    public function multiplierForApply($activityId, $amount = 0)
    {
        $rule = $this->multiplierRuleFor($activityId, $amount);
        if (!$rule || !isset($rule->multiplier)) {
            return 1.0;
        }

        return max(1.0, (float) $rule->multiplier);
    }

    public function gameRestrictionHit($user, $platform, $gameType = '', $gameCode = '')
    {
        if (!Schema::hasTable('tcg_user_game_restrictions')) {
            return null;
        }

        return $this->activeUserScopedQuery('tcg_user_game_restrictions', $user)
            ->whereIn('game_scope', $this->gameScopes($platform, $gameType, $gameCode))
            ->orderByDesc('id')
            ->first();
    }

    public function playerLimitFor($user, $platform, $gameType = '', $gameCode = '')
    {
        if (!Schema::hasTable('tcg_player_limit_rules')) {
            return null;
        }

        return $this->activeUserScopedQuery('tcg_player_limit_rules', $user)
            ->whereIn('game_scope', $this->gameScopes($platform, $gameType, $gameCode))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    public function amountExceedsPlayerLimit($user, $amount, $platform, $gameType = '', $gameCode = '')
    {
        $limit = $this->playerLimitFor($user, $platform, $gameType, $gameCode);

        if (!$limit || $limit->max_bet === null || $limit->max_bet === '') {
            return null;
        }

        return (float) $amount > (float) $limit->max_bet ? $limit : null;
    }

    private function activeUserScopedQuery($table, $user)
    {
        return DB::table($table)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $this->now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $this->now());
            })
            ->where(function ($query) use ($user) {
                $this->applyUserMatch($query, $user);
            });
    }

    private function gameScopes($platform, $gameType = '', $gameCode = '')
    {
        $platform = trim((string) $platform);
        $gameType = trim((string) $gameType);
        $gameCode = trim((string) $gameCode);

        $scopes = ['*', 'all'];

        foreach ([$platform, $gameType, $gameCode] as $scope) {
            if ($scope !== '') {
                $scopes[] = $scope;
                $scopes[] = strtoupper($scope);
                $scopes[] = strtolower($scope);
            }
        }

        if ($platform !== '' && $gameCode !== '') {
            $scopes[] = $platform . ':' . $gameCode;
            $scopes[] = strtoupper($platform) . ':' . strtoupper($gameCode);
            $scopes[] = strtolower($platform) . ':' . strtolower($gameCode);
        }

        return array_values(array_unique($scopes));
    }

    private function userId($user)
    {
        return $this->value($user, 'id');
    }

    private function username($user)
    {
        return $this->value($user, 'username');
    }

    private function applyUserMatch($query, $user)
    {
        $matched = false;
        $userId = $this->userId($user);
        $username = $this->username($user);

        if ($userId !== null && $userId !== '') {
            $query->where('user_id', $userId);
            $matched = true;
        }

        if ($username !== null && $username !== '') {
            $method = $matched ? 'orWhere' : 'where';
            $query->{$method}('username', $username);
            $matched = true;
        }

        if (!$matched) {
            $query->whereRaw('1 = 0');
        }
    }

    private function value($item, $key)
    {
        if (is_array($item)) {
            return array_key_exists($key, $item) ? $item[$key] : null;
        }

        if (is_object($item)) {
            return isset($item->{$key}) ? $item->{$key} : null;
        }

        return null;
    }

    private function now()
    {
        return date('Y-m-d H:i:s');
    }
}
