<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PlatformOperationsSiteSettingsTest extends TestCase
{
    public function test_site_settings_exposes_security_member_and_agent_sections()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Services/PlatformOperationsService.php';
        $this->assertFileExists($path);

        require_once $path;
        $service = new \App\Admin\Services\PlatformOperationsService();
        $page = $service->page('90510');

        $this->assertArrayHasKey('setting_sections', $page);
        $this->assertSame([
            'security',
            'member_center',
            'agent_center',
        ], array_keys($page['setting_sections']));

        $this->assertSame('安全设置', $page['setting_sections']['security']['title']);
        $this->assertContains('username_register_enabled', $page['setting_sections']['security']['fields']);
        $this->assertContains('login_unique_ip_enabled', $page['setting_sections']['security']['fields']);
        $this->assertContains('captcha_enabled', $page['setting_sections']['security']['fields']);

        $this->assertSame('会员中心', $page['setting_sections']['member_center']['title']);
        $this->assertContains('member_level_enabled', $page['setting_sections']['member_center']['fields']);
        $this->assertContains('member_rebate_enabled', $page['setting_sections']['member_center']['fields']);
        $this->assertContains('member_message_enabled', $page['setting_sections']['member_center']['fields']);

        $this->assertSame('代理中心', $page['setting_sections']['agent_center']['title']);
        $this->assertContains('agent_center_enabled', $page['setting_sections']['agent_center']['fields']);
        $this->assertContains('agent_register_type', $page['setting_sections']['agent_center']['fields']);
        $this->assertContains('agent_url', $page['setting_sections']['agent_center']['fields']);
    }

    public function test_site_settings_filter_accepts_all_section_fields()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Services/PlatformOperationsService.php';
        $this->assertFileExists($path);

        require_once $path;
        $service = new \App\Admin\Services\PlatformOperationsService();

        $filtered = $service->filterSettings('90510', [
            'username_register_enabled' => '1',
            'member_level_enabled' => '0',
            'agent_center_enabled' => '1',
            'agent_url' => 'https://agent.example.com',
            'not_allowed' => 'must be ignored',
        ]);

        $this->assertSame([
            'username_register_enabled' => '1',
            'member_level_enabled' => '0',
            'agent_center_enabled' => '1',
            'agent_url' => 'https://agent.example.com',
        ], $filtered);
    }

    public function test_site_settings_view_has_three_section_tabs()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/platform-operations.blade.php';
        $this->assertFileExists($path);

        $source = file_get_contents($path);

        foreach ([
            'platform-site-settings',
            'platform-site-settings-nav',
            'data-setting-section',
            '安全设置',
            '会员中心',
            '代理中心',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }
}
