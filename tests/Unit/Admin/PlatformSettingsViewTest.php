<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PlatformSettingsViewTest extends TestCase
{
    private function viewContents()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/platform-settings.blade.php';

        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    public function test_it_renders_six_independent_configuration_tabs()
    {
        $view = $this->viewContents();

        foreach ([
            '平台配置',
            '下载链接',
            '用户信息',
            '前台显示样式',
            'APP 打包',
            'APP 下载设置',
        ] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('@foreach($tabs as $tabKey => $tabLabel)', $view);
        $this->assertStringContainsString('@foreach($fields as $field)', $view);
        $this->assertStringContainsString('multipart/form-data', $view);
        $this->assertStringContainsString('js-platform-settings-form', $view);
    }

    public function test_platform_tab_contains_customer_service_management()
    {
        $view = $this->viewContents();

        $this->assertStringContainsString("@if(\$tab === 'platform')", $view);
        $this->assertStringContainsString('客服链接管理', $view);
        $this->assertStringContainsString('js-customer-service-create', $view);
        $this->assertStringContainsString('js-customer-service-edit', $view);
        $this->assertStringContainsString('js-customer-service-delete', $view);
        $this->assertStringContainsString("admin_url('tcg/platform-customer-services')", $view);
    }

    public function test_app_package_tab_contains_real_build_queue_controls()
    {
        $view = $this->viewContents();

        $this->assertStringContainsString("@if(\$tab === 'app-package')", $view);
        $this->assertStringContainsString('提交打包请求', $view);
        $this->assertStringContainsString('打包历史', $view);
        $this->assertStringContainsString('js-app-build-request', $view);
        $this->assertStringContainsString("admin_url('tcg/platform-app-builds')", $view);
    }

    public function test_platform_view_does_not_touch_pixel_tracking_features()
    {
        $view = $this->viewContents();

        $this->assertStringNotContainsString('12535', $view);
        $this->assertStringNotContainsString('pixel-config', $view);
        $this->assertStringNotContainsString('像素埋点', $view);
    }
}
