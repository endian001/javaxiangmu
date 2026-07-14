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

    public function test_activity_approval_issues_real_coupon_reward_when_amount_exists()
    {
        $migration = file_get_contents($this->root().'/database/migrations/2026_07_14_000001_extend_activity_apply_reward_fields.php');
        $baseController = file_get_contents($this->root().'/app/Http/Controllers/Controller.php');
        $passAction = file_get_contents($this->root().'/app/Admin/Actions/Grid/Activity/Pass.php');

        foreach ([
            'coupon_code',
            'reward_amount',
            'reward_source',
            'issued_transfer_log_id',
            'issued_at',
        ] as $field) {
            $this->assertStringContainsString($field, $migration);
        }

        foreach ([
            'coupon_code',
            'reward_amount',
            'reward_source',
        ] as $field) {
            $this->assertStringContainsString($field, $baseController);
        }

        $this->assertStringContainsString('function activityApplyPayload(', $baseController);
        $this->assertStringContainsString("Schema::hasColumn('activity_apply', 'reward_amount')", $baseController);

        foreach ([
            'use App\Models\TransferLog;',
            'use App\Models\Users;',
            'activityRewardAmount',
            'Users::where',
            'lockForUpdate()',
            'TransferLog::create([',
            "'transfer_type' => 5",
            'issued_transfer_log_id',
            'makeActivityRewardOrderNo',
        ] as $needle) {
            $this->assertStringContainsString($needle, $passAction);
        }
    }
}
