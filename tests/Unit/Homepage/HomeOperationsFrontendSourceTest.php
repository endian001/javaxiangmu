<?php

namespace Tests\Unit\Homepage;

use PHPUnit\Framework\TestCase;

class HomeOperationsFrontendSourceTest extends TestCase
{
    private function root()
    {
        return dirname(__DIR__, 3);
    }

    public function test_shared_home_operations_assets_exist()
    {
        $this->assertFileExists($this->root().'/public/assets/home-operations.js');
        $this->assertFileExists($this->root().'/public/assets/home-operations.css');
    }

    public function test_desktop_and_mobile_home_load_the_shared_component()
    {
        foreach (['/public/index.html', '/public/new-h5/index.html'] as $file) {
            $source = file_get_contents($this->root().$file);

            $this->assertStringContainsString('data-home-banner', $source);
            $this->assertStringContainsString(
                '/assets/home-operations.css?v=20260711home1',
                $source
            );
            $this->assertStringContainsString(
                '/assets/home-operations.js?v=20260711home1',
                $source
            );
        }
    }

    public function test_component_uses_real_banner_and_customer_service_apis()
    {
        $script = file_get_contents($this->root().'/public/assets/home-operations.js');
        $styles = file_get_contents($this->root().'/public/assets/home-operations.css');

        foreach ([
            '/api/bannerList',
            '/api/getservicerurl',
            'data-contact-toggle',
            'data-contact-panel',
            'data-online-service',
            'data-back-to-top',
            'data-floating-promo',
            'touchstart',
            'aria-expanded',
        ] as $marker) {
            $this->assertStringContainsString($marker, $script);
        }

        foreach ([
            '.home-carousel',
            '.floating-support',
            '.floating-support__contacts',
            '@media (max-width: 640px)',
        ] as $marker) {
            $this->assertStringContainsString($marker, $styles);
        }
    }

    public function test_default_banner_assets_and_mobile_manifest_are_real()
    {
        foreach ([
            '/public/assets/promotions/welcome-banner.png',
            '/public/assets/promotions/deposit-banner.png',
            '/public/new-h5/manifest.json',
        ] as $file) {
            $this->assertFileExists($this->root().$file);
        }

        $controller = file_get_contents(
            $this->root().'/app/Http/Controllers/Api/IndexController.php'
        );
        $this->assertStringContainsString(
            '/assets/promotions/welcome-banner.png',
            $controller
        );
        $this->assertStringContainsString(
            '/assets/promotions/deposit-banner.png',
            $controller
        );
        $this->assertStringNotContainsString('/static/style/', $controller);

        $manifest = json_decode(
            file_get_contents($this->root().'/public/new-h5/manifest.json'),
            true
        );
        $this->assertSame('TH2W', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
    }
}
