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

    public function test_activity_admin_labels_are_chinese()
    {
        $activitySource = file_get_contents($this->root().'/app/Admin/Controllers/ActivityController.php');
        $typeSource = file_get_contents($this->root().'/app/Admin/Controllers/ActivityTypeController.php');
        $source = $activitySource."\n".$typeSource;

        foreach ([
            'Category',
            'Legacy title',
            'Thai title',
            'Legacy content',
            'Thai content',
            'Apply count',
            'Desktop status',
            'Mobile status',
            'Thai category',
            'Enabled',
            'Disabled',
        ] as $legacyLabel) {
            $this->assertStringNotContainsString($legacyLabel, $source);
        }

        foreach ([
            '活动分类',
            '中文标题',
            '前台泰文标题',
            '首页弹窗',
            '电脑端状态',
            '手机端状态',
            '后台中文分类',
            '状态',
        ] as $chineseLabel) {
            $this->assertStringContainsString($chineseLabel, $source);
        }
    }

    public function test_promotion_api_uses_public_category_name_without_changing_admin_name()
    {
        foreach ([
            '/app/Http/Controllers/Api/PromotionController.php',
            '/app/Http/Controllers/Api/IndexController.php',
            '/app/Http/Controllers/Api/AppController.php',
        ] as $path) {
            $source = file_get_contents($this->root().$path);
            $this->assertStringContainsString('activityTypePublicName', $source);
            $this->assertStringContainsString('enname', $source);
        }
    }
}
