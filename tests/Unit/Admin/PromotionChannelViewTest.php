<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PromotionChannelViewTest extends TestCase
{
    public function test_view_contains_all_non_pixel_module_surfaces()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/promotion-channel.blade.php';
        $this->assertFileExists($path);

        $view = file_get_contents($path);

        foreach ([
            '新增主站投放链接',
            '新增落地页面投放链接',
            '新增测速页投放链接',
            '推广域名管理',
            '落地页配置',
            'SEO配置',
            '推播设置',
            '推播记录',
            '像素事件',
            '调整S2S事件上报',
        ] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('js-promotion-item-save', $view);
        $this->assertStringContainsString('js-promotion-bulk-delete', $view);
        $this->assertStringContainsString('js-promotion-settings-save', $view);
        $this->assertStringContainsString('js-promotion-push-submit', $view);
        $this->assertStringContainsString("admin_url('tcg/promotion')", $view);
        $this->assertStringNotContainsString('pixel-config', $view);
        $this->assertStringNotContainsString('/tcg/12535', $view);
    }
}
