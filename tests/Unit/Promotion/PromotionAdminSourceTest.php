<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class PromotionAdminSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_activity_admin_exposes_publication_and_popup_controls()
    {
        $source = file_get_contents($this->root().'/app/Admin/Controllers/ActivityController.php');

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
        ] as $field) {
            $this->assertStringContainsString($field, $source);
        }
    }

    public function test_activity_type_admin_exposes_sorting()
    {
        $source = file_get_contents($this->root().'/app/Admin/Controllers/ActivityTypeController.php');
        $this->assertStringContainsString('sort_order', $source);
    }
}
