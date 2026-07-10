<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\PlatformSettingsController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PlatformSettingsRoutesTest extends TestCase
{
    public function test_platform_routes_are_registered_before_generic_tcg_route()
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/app/Admin/routes.php');
        $genericPosition = strpos($routes, "'/tcg/{code}'");
        $platformPosition = strpos($routes, "'/tcg/90400'");

        $this->assertNotFalse($platformPosition);
        $this->assertLessThan($genericPosition, $platformPosition);

        foreach ([
            '/tcg/platform-settings/{tab}',
            '/tcg/platform-customer-services',
            '/tcg/platform-customer-services/{id}',
            '/tcg/platform-app-builds',
        ] as $path) {
            $this->assertStringContainsString($path, $routes);
        }
    }

    public function test_platform_controller_exposes_required_actions()
    {
        $controller = new ReflectionClass(PlatformSettingsController::class);

        foreach ([
            'index',
            'saveTab',
            'saveCustomerService',
            'deleteCustomerService',
            'requestAppBuild',
        ] as $method) {
            $this->assertTrue($controller->hasMethod($method));
        }
    }
}
