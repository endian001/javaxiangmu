<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class PromotionFrontendSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_shared_promotion_assets_exist()
    {
        foreach ([
            '/public/assets/promotion-core.js',
            '/public/assets/promotion-system.js',
            '/public/assets/promotion-system.css',
            '/public/assets/promotions/welcome-banner.png',
            '/public/assets/promotions/welcome-detail.png',
            '/public/assets/promotions/deposit-banner.png',
            '/public/assets/promotions/deposit-detail.png',
            '/public/assets/promotions/referral-banner.webp',
            '/public/assets/promotions/referral-detail.jpg',
        ] as $file) {
            $this->assertFileExists($this->root().$file);
        }
    }

    public function test_both_home_entries_load_shared_promotion_assets()
    {
        foreach (['/public/index.html', '/public/new-h5/index.html'] as $file) {
            $source = file_get_contents($this->root().$file);
            $this->assertStringContainsString('/assets/promotion-system.css?v=', $source);
            $this->assertStringContainsString('/assets/promotion-core.js?v=', $source);
            $this->assertStringContainsString('/assets/promotion-system.js?v=', $source);
        }
    }

    public function test_detail_modal_has_long_form_visual_structure()
    {
        $script = file_get_contents($this->root().'/public/assets/promotion-system.js');
        $styles = file_get_contents($this->root().'/public/assets/promotion-system.css');

        $this->assertStringContainsString("promotionImage(item, 'detail')", $script);
        $this->assertStringContainsString('promo-detail-scroller', $script);
        $this->assertStringContainsString('promo-detail-artwork', $script);
        $this->assertStringContainsString('.promo-detail-scroller', $styles);
        $this->assertStringContainsString('.promo-detail-artwork', $styles);
    }

    public function test_popup_and_apply_routes_are_wired()
    {
        $script = file_get_contents($this->root().'/public/assets/promotion-system.js');
        $routes = file_get_contents($this->root().'/routes/api.php');

        $this->assertStringContainsString('/api/promotions/popup', $script);
        $this->assertStringContainsString('/api/promotions/', $script);
        $this->assertStringContainsString('/apply', $script);
        $this->assertStringContainsString('/api/doactivityapply', $script);
        $this->assertStringContainsString('promo-home-panel--split', $script);
        $this->assertStringContainsString("Route::post('/promotions/{id}/apply'", $routes);
    }

    public function test_frontend_preserves_api_text_and_thai_copy()
    {
        $script = file_get_contents($this->root().'/public/assets/promotion-system.js');

        $this->assertStringNotContainsString('function needsChineseFallback', $script);
        $this->assertStringNotContainsString('function stripThaiFragments', $script);
        $this->assertStringNotContainsString('hasThaiText(text) || hasBrokenText(text) ? fallback : text', $script);
        $this->assertStringNotContainsString('source.title = preset.title', $script);
        $this->assertStringNotContainsString('source.type_name = preset.type_name', $script);
        $this->assertStringNotContainsString('source.category_name = preset.type_name', $script);
        $this->assertStringNotContainsString('source.entitle = preset.entitle', $script);
        $this->assertStringNotContainsString('source.button_text = preset.button_text', $script);
        $this->assertStringNotContainsString('???', $script);

        $this->assertStringContainsString('function needsFallbackText', $script);
        $this->assertStringContainsString('return !text || hasBrokenText(text) ? fallback : text;', $script);
    }

    public function test_promotion_artwork_has_expected_dimensions()
    {
        foreach (['welcome', 'deposit'] as $campaign) {
            $banner = getimagesize($this->root().'/public/assets/promotions/'.$campaign.'-banner.png');
            $detail = getimagesize($this->root().'/public/assets/promotions/'.$campaign.'-detail.png');

            $this->assertSame([1040, 548], [$banner[0], $banner[1]]);
            $this->assertSame([1040, 2930], [$detail[0], $detail[1]]);
        }

        $referralBanner = getimagesize($this->root().'/public/assets/promotions/referral-banner.webp');
        $referralDetail = getimagesize($this->root().'/public/assets/promotions/referral-detail.jpg');

        $this->assertSame([900, 474], [$referralBanner[0], $referralBanner[1]]);
        $this->assertSame([1040, 6742], [$referralDetail[0], $referralDetail[1]]);
    }
}
