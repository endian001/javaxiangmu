<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\PlatformSettingsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PlatformSettingsServiceTest extends TestCase
{
    public function test_it_exposes_the_seven_benchmark_tabs()
    {
        $service = new PlatformSettingsService();

        $this->assertSame([
            'platform',
            'download-links',
            'user-info',
            'frontend-style',
            'app-package',
            'app-download',
            'wxgame',
        ], array_keys($service->tabs()));
    }

    public function test_wxgame_tab_exposes_connection_and_callback_settings()
    {
        $service = new PlatformSettingsService();
        $keys = array_column($service->fields('wxgame'), 'key');

        foreach ([
            'wxgame_enabled',
            'wxgame_api_domain',
            'wxgame_access_key_id',
            'wxgame_access_key_secret',
            'wxgame_app_id',
            'wxgame_callback_domain',
            'wxgame_currency',
            'wxgame_token_secret',
            'wxgame_callback_signature_required',
            'wxgame_callback_sign_window',
            'wxgame_ssl_verify',
        ] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_it_filters_values_to_the_selected_tab_schema()
    {
        $service = new PlatformSettingsService();

        $this->assertSame([
            'platform_maintenance' => '1',
            'platform_main_domain' => 'https://example.com',
        ], $service->filterValues('platform', [
            'platform_maintenance' => '1',
            'platform_main_domain' => 'https://example.com',
            'api_secret' => 'must-not-pass',
        ]));
    }

    public function test_it_rejects_unknown_tabs()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('平台配置标签不存在');

        (new PlatformSettingsService())->fields('unknown');
    }
}
