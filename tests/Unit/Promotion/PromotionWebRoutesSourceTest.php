<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class PromotionWebRoutesSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_web_routes_include_static_promotion_entrypoints()
    {
        $source = file_get_contents($this->root().'/routes/web.php');

        foreach (["'/activity'", "'/activities'", "'/promotions'"] as $route) {
            $this->assertStringContainsString($route, $source);
        }
        $this->assertStringContainsString('promotionEntry', $source);
    }
}
