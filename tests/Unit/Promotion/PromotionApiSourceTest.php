<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class PromotionApiSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_promotion_controller_exposes_complete_read_api()
    {
        $path = $this->root().'/app/Http/Controllers/Api/PromotionController.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach (['categories', 'index', 'show', 'popup', 'recordExposure'] as $method) {
            $this->assertStringContainsString('function '.$method.'(', $source);
        }
    }

    public function test_api_routes_include_complete_promotion_contract()
    {
        $source = file_get_contents($this->root().'/routes/api.php');

        foreach ([
            '/promotions/categories',
            '/promotions/popup',
            '/promotions/{id}/exposure',
            '/promotions/{id}',
            '/promotions',
        ] as $route) {
            $this->assertStringContainsString($route, $source);
        }
    }

    public function test_controller_preserves_public_asset_urls()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Api/PromotionController.php');

        $this->assertStringContainsString("strpos(\$path, '/assets/') === 0", $source);
        $this->assertStringContainsString("rtrim((string) env('APP_URL'), '/') . \$path", $source);
    }
}
