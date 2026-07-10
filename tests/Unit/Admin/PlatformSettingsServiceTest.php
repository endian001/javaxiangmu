<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\PlatformSettingsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PlatformSettingsServiceTest extends TestCase
{
    public function test_it_exposes_the_six_benchmark_tabs()
    {
        $service = new PlatformSettingsService();

        $this->assertSame([
            'platform',
            'download-links',
            'user-info',
            'frontend-style',
            'app-package',
            'app-download',
        ], array_keys($service->tabs()));
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
