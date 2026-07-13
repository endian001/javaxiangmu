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

            $this->assertTrue(
                strpos($source, 'data-home-banner') !== false || strpos($source, 'data-reference-banner') !== false,
                $file.' should expose a home banner hook'
            );
            $this->assertStringContainsString(
                '/assets/home-operations.css?v=20260713ops4',
                $source
            );
            $this->assertStringContainsString(
                '/assets/home-operations.js?v=20260713ops4',
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
            'data-home-search-form',
            'data-guest-actions',
            'data-member-name',
            '/api/user',
            '/gaming?keyword=',
            'touchstart',
            'aria-expanded',
        ] as $marker) {
            $this->assertStringContainsString($marker, $script);
        }

        foreach ([
            '.home-carousel',
            '.brand-logo',
            '.top-search',
            '.language-switch',
            '.member-chip',
            '.floating-support',
            '.floating-support__contacts',
            '@media (max-width: 640px)',
        ] as $marker) {
            $this->assertStringContainsString($marker, $styles);
        }
    }

    public function test_home_operations_uses_unified_customer_service_opening_flow()
    {
        $script = file_get_contents($this->root().'/public/assets/home-operations.js');

        $this->assertStringContainsString('function openCustomerService(', $script);
        $this->assertStringContainsString('function resolveCustomerServiceUrl(', $script);
        $this->assertStringContainsString('fallback_url', $script);
        $this->assertStringContainsString('/api/getservicerurl', $script);
        $this->assertStringNotContainsString(
            'data-online-service href="/support/work-orders.html"',
            $script,
            'The online customer-service entry should be wired through the shared opener, not permanently hard-coded to work orders.'
        );
        $this->assertStringNotContainsString(
            "renderCustomerServices(root, { services: [], service_url: '/support/work-orders.html' });",
            $script,
            'Work orders may remain a fallback, but should not be the default for every customer-service entry before the API resolves.'
        );
    }

    public function test_mobile_bottom_customer_service_entry_uses_shared_customer_service_logic()
    {
        $source = file_get_contents($this->root().'/public/new-h5/index.html');

        $this->assertStringNotContainsString(
            'class="bn" href="/support/work-orders.html"',
            $source,
            'The mobile bottom customer-service tab should not be permanently hard-coded to the work-order page.'
        );
        $this->assertTrue(
            strpos($source, 'data-customer-service') !== false ||
            strpos($source, '/api/getservicerurl') !== false,
            'The mobile bottom customer-service tab should use the shared customer-service flow or fetch /api/getservicerurl.'
        );
    }

    public function test_home_operations_does_not_strip_thai_text()
    {
        $script = file_get_contents($this->root().'/public/assets/home-operations.js');

        $this->assertStringContainsString('function localizeVisibleCopy(root)', $script);
        $this->assertStringContainsString('return root || document.body;', $script);
        $this->assertStringNotContainsString('thaiRunPattern', $script);
        $this->assertStringNotContainsString('NodeFilter.SHOW_TEXT', $script);
    }

    public function test_desktop_and_mobile_top_bar_match_reference_controls()
    {
        $expected = [
            'brand-logo',
            'data-guest-actions',
            'data-member-actions',
            'language-switch',
            '/member/center',
            '/login',
            '/register',
        ];

        foreach (['/public/index.html', '/public/new-h5/index.html'] as $file) {
            $source = file_get_contents($this->root().$file);
            foreach ($expected as $marker) {
                $this->assertStringContainsString($marker, $source);
            }

            if ($file === '/public/index.html') {
                $this->assertStringContainsString('data-home-search-form', $source);
                $this->assertStringContainsString('data-home-search-input', $source);
            }

            $this->assertStringNotContainsString('data-member-actions hidden', $source);
        }
    }

    public function test_default_banner_assets_and_mobile_manifest_are_real()
    {
        foreach ([
            '/public/assets/promotions/welcome-banner.png',
            '/public/assets/promotions/deposit-banner.png',
            '/public/new-h5/manifest.json',
            '/public/uploads/th2w-ui/game-icons/flame.svg',
            '/public/uploads/th2w-ui/game-icons/cherry.svg',
            '/public/uploads/th2w-ui/game-icons/dices.svg',
            '/public/uploads/th2w-ui/game-icons/fish.svg',
            '/public/uploads/th2w-ui/game-icons/trophy.svg',
            '/public/uploads/th2w-ui/game-icons/spade.svg',
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
