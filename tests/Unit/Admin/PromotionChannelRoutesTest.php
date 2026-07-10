<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\PromotionChannelController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PromotionChannelRoutesTest extends TestCase
{
    public function test_promotion_routes_are_registered_before_generic_tcg_route()
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/app/Admin/routes.php');
        $genericPosition = strpos($routes, "'/tcg/{code}'");

        foreach ([
            '/tcg/280000',
            '/tcg/21160',
            '/tcg/280004',
            '/tcg/280008',
            '/tcg/280015',
            '/tcg/280012',
            '/tcg/promotion/{code}/items',
            '/tcg/promotion/{code}/bulk-delete',
            '/tcg/promotion/{code}/settings',
            '/tcg/promotion/{code}/push-jobs',
            '/tcg/promotion/{code}/events/export',
        ] as $path) {
            $position = strpos($routes, $path);
            $this->assertNotFalse($position, $path.' route missing');
            $this->assertLessThan($genericPosition, $position, $path.' route order');
        }
    }

    public function test_promotion_controller_exposes_required_actions()
    {
        $this->assertTrue(class_exists(PromotionChannelController::class));

        $controller = new ReflectionClass(PromotionChannelController::class);
        foreach ([
            'index',
            'saveItem',
            'deleteItem',
            'bulkDelete',
            'saveSettings',
            'createPushJob',
            'exportEvents',
        ] as $method) {
            $this->assertTrue($controller->hasMethod($method), $method);
        }
    }
}
