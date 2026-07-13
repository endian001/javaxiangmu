<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class WxgameAdminSourceTest extends TestCase
{
    public function test_wxgame_has_admin_settings_and_real_callback_endpoint_defaults()
    {
        $settingsPath = dirname(__DIR__, 3).'/app/Admin/Services/PlatformSettingsService.php';
        $apiPath = dirname(__DIR__, 3).'/app/Http/Controllers/Api/IndexController.php';
        $this->assertFileExists($settingsPath);
        $this->assertFileExists($apiPath);

        $settings = file_get_contents($settingsPath);
        $api = file_get_contents($apiPath);

        $this->assertStringContainsString("'wxgame' => 'WXGAME'", $settings);
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
        ] as $key) {
            $this->assertStringContainsString($key, $settings);
        }

        $this->assertStringContainsString("\$appUrl . '/notify'", $api);
        $this->assertStringContainsString('availableEndpointBases', $api);
        $this->assertStringContainsString("'api_wxgame' => \$appUrl . '/api/wxgame'", $api);
    }
}
