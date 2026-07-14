<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class PromotionApiSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_promotion_controller_exposes_complete_read_api()
    {
        $path = $this->root().'/app/Http/Controllers/Api/PromotionController.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach (['categories', 'index', 'show', 'popup', 'recordExposure'] as $method) {
            $this->assertStringContainsString('function '.$method.'(', $source);
        }
    }

    public function test_api_routes_include_complete_promotion_contract()
    {
        $source = file_get_contents($this->root().'/routes/api.php');

        foreach ([
            '/promotions/categories',
            '/promotions/popup',
            '/promotions/{id}/exposure',
            '/promotions/{id}',
            '/promotions',
        ] as $route) {
            $this->assertStringContainsString($route, $source);
        }
    }

    public function test_controller_preserves_public_asset_urls()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/PromotionController.php');

        $this->assertStringContainsString("strpos(\$path, '/assets/') === 0", $source);
        $this->assertStringContainsString("rtrim((string) env('APP_URL'), '/') . \$path", $source);
    }

    public function test_legacy_activity_api_uses_the_same_promotion_contract()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/IndexController.php');

        $this->assertStringContainsString('use App\Services\PromotionService;', $source);
        $this->assertStringContainsString('promotionVisibleActivities', $source);
        $this->assertStringContainsString('legacyPromotionPayload', $source);

        foreach ([
            'popup_image',
            'app_popup_image',
            'detail_image',
            'app_detail_image',
            'button_text',
            'requires_auth',
            'popup_frequency',
            'popup_delay_seconds',
            'sort_order',
            'starts_at',
            'ends_at',
        ] as $field) {
            $this->assertStringContainsString($field, $source);
        }

        $this->assertStringContainsString("strpos(\$path, '/assets/') === 0", $source);
        $this->assertStringContainsString("new PromotionService()", $source);
    }

    public function test_legacy_activity_categories_respect_status_and_sort_order()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/IndexController.php');
        $method = substr($source, strpos($source, 'public function activityType'));
        $method = substr($method, 0, strpos($method, 'public function activityList'));

        $this->assertStringContainsString("where('state', 1)", $method);
        $this->assertStringContainsString("orderBy('sort_order'", $method);
    }

    public function test_app_activity_api_uses_shared_promotion_visibility()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/AppController.php');

        $this->assertStringContainsString('use App\Services\PromotionService;', $source);
        $this->assertStringContainsString('appPromotionPayload', $source);
        $this->assertStringContainsString('new PromotionService()', $source);
        $this->assertStringContainsString('sort_order', $source);
        $this->assertStringContainsString('starts_at', $source);
        $this->assertStringContainsString('ends_at', $source);
    }

    public function test_app_activity_apply_checks_date_windows_and_duplicate_insert_errors()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/AppController.php');
        $method = substr($source, strpos($source, 'public function activitiesgo'));
        $method = substr($method, 0, strpos($method, 'public function activities(Request'));

        $this->assertStringContainsString('PromotionService', $method);
        $this->assertStringContainsString('visible([$activity]', $method);
        $this->assertStringContainsString("stripos(\$e->getMessage(), 'Duplicate')", $method);
    }

    public function test_activity_apply_endpoints_use_tcg_activity_blacklist_before_create()
    {
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Controller.php');
        $service = file_get_contents($this->root().'/app/Services/TcgBusinessOperationService.php');

        foreach ([
            'activityBlacklistHit',
            'TcgBusinessOperationService',
            'tcgBusinessOperations',
            'activityBlacklistMessage',
        ] as $needle) {
            $this->assertStringContainsString($needle, $controller);
        }

        foreach ([
            'tcg_activity_blacklists',
            "where('status', 'active')",
            "where('user_id', \$userId)",
            "\$method = \$matched ? 'orWhere' : 'where';",
            "\$query->{\$method}('username', \$username)",
            "whereNull('activity_id')",
            "orWhere('activity_id', \$activityId)",
            "whereNull('starts_at')",
            "whereNull('ends_at')",
            'activityBlacklistMessage',
        ] as $needle) {
            $this->assertStringContainsString($needle, $service);
        }

        $targets = [
            [$this->root().'/app/Http/Controllers/Api/PromotionController.php', 'public function apply', 'ActivityApply::create'],
            [$this->root().'/app/Http/Controllers/Api/IndexController.php', 'public function doactivity', 'ActivityApply::create'],
            [$this->root().'/app/Http/Controllers/Api/AppController.php', 'public function activitiesgo', 'ActivityApply::create'],
            [$this->root().'/app/Http/Controllers/Wap/IndexController.php', 'public function doactivity', 'ActivityApply::create'],
            [$this->root().'/app/Http/Controllers/Member/MemberController.php', 'public function doactivity', 'ActivityApply::create'],
        ];

        foreach ($targets as [$path, $methodNeedle, $createNeedle]) {
            $source = file_get_contents($path);
            $method = substr($source, strpos($source, $methodNeedle));
            $blacklistAt = strpos($method, 'activityBlacklistHit');
            $createAt = strpos($method, $createNeedle);

            $this->assertNotFalse($blacklistAt, $path);
            $this->assertNotFalse($createAt, $path);
            $this->assertLessThan($createAt, $blacklistAt, $path);
        }
    }

    public function test_activity_apply_endpoints_consume_tcg_coupon_before_create()
    {
        $targets = [
            [$this->root().'/app/Http/Controllers/Api/PromotionController.php', 'public function apply', 'ActivityApply::create'],
            [$this->root().'/app/Http/Controllers/Api/IndexController.php', 'public function doactivity', 'ActivityApply::create'],
            [$this->root().'/app/Http/Controllers/Api/AppController.php', 'public function activitiesgo', 'ActivityApply::create'],
        ];

        foreach ($targets as [$path, $methodNeedle, $createNeedle]) {
            $source = file_get_contents($path);
            $method = substr($source, strpos($source, $methodNeedle));
            $couponAt = strpos($method, 'validateActivityCouponForApply');
            $createAt = strpos($method, $createNeedle);

            $this->assertNotFalse($couponAt, $path);
            $this->assertNotFalse($createAt, $path);
            $this->assertLessThan($createAt, $couponAt, $path);
        }
    }

    public function test_activity_apply_endpoints_store_reward_payload_for_admin_issuance()
    {
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Controller.php');
        $this->assertStringContainsString('function activityApplyPayload(', $controller);
        $this->assertStringContainsString('activityCouponAmount', $controller);

        $targets = [
            [$this->root().'/app/Http/Controllers/Api/PromotionController.php', 'public function apply'],
            [$this->root().'/app/Http/Controllers/Api/IndexController.php', 'public function doactivity'],
            [$this->root().'/app/Http/Controllers/Api/AppController.php', 'public function activitiesgo'],
            [$this->root().'/app/Http/Controllers/Wap/IndexController.php', 'public function doactivity'],
            [$this->root().'/app/Http/Controllers/Member/MemberController.php', 'public function doactivity'],
        ];

        foreach ($targets as [$path, $methodNeedle]) {
            $source = file_get_contents($path);
            $method = substr($source, strpos($source, $methodNeedle));

            $this->assertStringContainsString('activityApplyPayload', $method, $path);
            $this->assertStringContainsString('ActivityApply::create', $method, $path);
        }
    }

    public function test_tcg_business_operation_service_is_used_by_frontend_runtime_paths()
    {
        $servicePath = $this->root().'/app/Services/TcgBusinessOperationService.php';
        $this->assertFileExists($servicePath);
        $service = file_get_contents($servicePath);

        foreach ([
            'tcg_activity_blacklists',
            'tcg_activity_coupons',
            'tcg_activity_multiplier_rules',
            'tcg_player_limit_rules',
            'tcg_user_game_restrictions',
            'couponForApply',
            'markCouponUsed',
            'gameRestrictionHit',
            'amountExceedsPlayerLimit',
        ] as $needle) {
            $this->assertStringContainsString($needle, $service);
        }

        $controller = file_get_contents($this->root().'/app/Http/Controllers/Controller.php');
        $this->assertStringContainsString('TcgBusinessOperationService', $controller);
        $this->assertStringContainsString('validateActivityCouponForApply', $controller);
        $this->assertStringContainsString('gameRestrictionHit', $controller);
        $this->assertStringContainsString('amountExceedsPlayerLimit', $controller);

        $runtimeChecks = [
            $this->root().'/app/Http/Controllers/Api/PromotionController.php' => ['validateActivityCouponForApply', 'markActivityCouponUsed'],
            $this->root().'/app/Http/Controllers/Api/IndexController.php' => ['validateActivityCouponForApply', 'markActivityCouponUsed', 'gameRestrictionHit', 'amountExceedsPlayerLimit'],
            $this->root().'/app/Http/Controllers/Api/AppController.php' => ['validateActivityCouponForApply', 'markActivityCouponUsed', 'gameRestrictionHit'],
            $this->root().'/app/Http/Controllers/Api/PayController.php' => ['amountExceedsPlayerLimit'],
            $this->root().'/app/Services/SafeGameTransferService.php' => ['TcgBusinessOperationService', 'amountExceedsPlayerLimit'],
        ];

        foreach ($runtimeChecks as $path => $needles) {
            $source = file_get_contents($path);
            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $source, $path);
            }
        }
    }

    public function test_legacy_web_activity_apply_methods_have_no_unreachable_legacy_branch()
    {
        $targets = [
            [$this->root().'/app/Http/Controllers/Wap/IndexController.php', 'public function doactivity', 'public function recharge'],
            [$this->root().'/app/Http/Controllers/Member/MemberController.php', 'public function doactivity', 'public function progress'],
        ];

        foreach ($targets as [$path, $startNeedle, $endNeedle]) {
            $source = file_get_contents($path);
            $method = substr($source, strpos($source, $startNeedle));
            $method = substr($method, 0, strpos($method, $endNeedle));

            $this->assertSame(1, substr_count($method, 'ActivityApply::create'), $path);
            $this->assertStringNotContainsString("created_at'] = time()", $method, $path);
            $this->assertStringNotContainsString('璇疯緭鍏', $method, $path);
            $this->assertStringNotContainsString('鐢宠', $method, $path);
        }
    }

    public function test_banner_list_respects_enabled_state_and_sort_order()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/IndexController.php');

        $this->assertStringContainsString("Banner::where('type', \$type)", $source);
        $this->assertStringContainsString("where('state', 1)", $source);
        $this->assertStringContainsString("orderBy('order'", $source);
        $this->assertStringContainsString("\$this->formatUploadUrl(\$val['src'] ?? '')", $source);
    }
}
