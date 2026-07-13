<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class PromotionMigrationSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_migration_contains_required_activity_fields_and_exposures()
    {
        $path = $this->root().'/database/migrations/2026_07_11_000001_extend_activity_promotions.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach ([
            'sort_order',
            'starts_at',
            'ends_at',
            'is_popup',
            'popup_frequency',
            'popup_delay_seconds',
            'popup_image',
            'app_popup_image',
            'detail_image',
            'app_detail_image',
            'action_url',
            'requires_auth',
            'promotion_exposures',
        ] as $field) {
            $this->assertStringContainsString($field, $source);
        }
    }

    public function test_thai_artwork_migration_replaces_legacy_chinese_images()
    {
        $path = $this->root().'/database/migrations/2026_07_11_000003_replace_legacy_activity_artwork.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach ([
            '/assets/promotions/welcome-banner.png',
            '/assets/promotions/welcome-detail.png',
            '/assets/promotions/deposit-banner.png',
            '/assets/promotions/deposit-detail.png',
        ] as $asset) {
            $this->assertStringContainsString($asset, $source);
        }
    }

    public function test_referral_activity_migration_connects_the_campaign_to_the_invite_center()
    {
        $path = $this->root().'/database/migrations/2026_07_11_000004_add_referral_activity.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach ([
            'แนะนำเพื่อน',
            '/assets/promotions/referral-banner.webp',
            '/assets/promotions/referral-detail.jpg',
            "'is_popup' => 0",
            "'action_url' => ''",
            "'requires_auth' => 0",
            "where('banner', '/assets/promotions/referral-banner.webp')",
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }

        $this->assertStringNotContainsString("orWhere('entitle'", $source);
    }

    public function test_referral_hardening_migration_removes_the_unsafe_action()
    {
        $path = $this->root().'/database/migrations/2026_07_11_000005_harden_referral_activity.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        $this->assertStringContainsString("where('banner', '/assets/promotions/referral-banner.webp')", $source);
        $this->assertStringContainsString("'action_url' => ''", $source);
        $this->assertStringContainsString("'requires_auth' => 0", $source);
        $this->assertStringContainsString("'is_popup' => 0", $source);
    }

    public function test_activity_admin_localization_migration_separates_admin_and_public_copy()
    {
        $path = $this->root().'/database/migrations/2026_07_13_000009_localize_activity_admin_content.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach ([
            "Schema::hasColumn('activity_types', 'enname')",
            "'name' => '新会员'",
            "'enname' => 'สมาชิกใหม่'",
            "'title' => '新会员最高 1,888 奖金'",
            "'entitle' => 'ต้อนรับสมาชิกใหม่ โบนัสสูงสุด 1,888'",
            "'title' => '邀请好友最高 28,888 奖金'",
            "'entitle' => 'ชวนเพื่อน รับโบนัสสูงสุด 28,888'",
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
    }

    public function test_referral_migrations_keep_the_frontend_action_hardened()
    {
        foreach ([
            '/database/migrations/2026_07_11_000004_add_referral_activity.php',
            '/database/migrations/2026_07_11_000006_clean_activity_promotions_final.php',
            '/database/migrations/2026_07_13_000009_localize_activity_admin_content.php',
            '/database/migrations/2026_07_13_000010_harden_localized_referral_action.php',
        ] as $path) {
            $source = file_get_contents($this->root().$path);
            $this->assertStringContainsString("'action_url' => ''", $source);
            $this->assertStringContainsString("'requires_auth' => 0", $source);
        }
    }

    public function test_late_legacy_activity_type_names_are_localized_for_admin()
    {
        $path = $this->root().'/database/migrations/2026_07_13_000011_localize_legacy_activity_type_names.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach ([
            "'name' => '每日福利'",
            "'enname' => 'สวัสดิการรายวัน'",
            "'name' => '下载APP'",
            "'enname' => 'ดาวน์โหลดแอป'",
            "'name' => '电子老虎机'",
            "'enname' => 'สล็อต'",
            "'name' => '其他'",
            "'enname' => 'อื่นๆ'",
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
    }
}
