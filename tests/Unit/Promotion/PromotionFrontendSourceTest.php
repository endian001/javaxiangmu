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
            $this->assertStringContainsString('/assets/promotion-system.css?v=20260711activity3', $source);
            $this->assertStringContainsString('/assets/promotion-core.js?v=20260711activity3', $source);
            $this->assertStringContainsString('/assets/promotion-system.js?v=20260711activity3', $source);
        }
    }

    public function test_detail_modal_has_long_form_visual_structure()
    {
        $script = file_get_contents($this->root().'/public/assets/promotion-system.js');
        $styles = file_get_contents($this->root().'/public/assets/promotion-system.css');

        $this->assertStringContainsString('hasDistinctDetailImage', $script);
        $this->assertStringContainsString('promo-detail-scroller', $script);
        $this->assertStringContainsString('promo-detail-artwork', $script);
        $this->assertStringContainsString('.promo-detail-scroller', $styles);
        $this->assertStringContainsString('.promo-detail-artwork', $styles);
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
