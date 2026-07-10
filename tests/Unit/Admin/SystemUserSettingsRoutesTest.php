<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\SystemUserSettingsController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SystemUserSettingsRoutesTest extends TestCase
{
    public function test_six_pages_are_registered_before_the_generic_tcg_route()
    {
        $routes = file_get_contents(
            dirname(__DIR__, 3).'/app/Admin/routes.php'
        );
        $genericPosition = strpos($routes, "'/tcg/{code}'");

        foreach (['10201', '10900', '10419', '10600', '10700', '10300'] as $code) {
            $position = strpos($routes, "'/tcg/{$code}'");
            $this->assertNotFalse($position, "Missing route for {$code}");
            $this->assertLessThan(
                $genericPosition,
                $position,
                "Route {$code} must be registered before the generic route"
            );
        }
    }

    public function test_mutation_and_export_routes_are_registered()
    {
        $routes = file_get_contents(
            dirname(__DIR__, 3).'/app/Admin/routes.php'
        );

        foreach ([
            '/tcg/system-users/{id}',
            '/tcg/system-roles/{id}/permissions',
            '/tcg/ip-whitelists',
            '/tcg/ip-whitelists/{id}',
            '/tcg/tasks/run',
            '/tcg/tasks/{id}/retry',
            '/tcg/task-history/export',
            '/tcg/system-logs/export',
        ] as $path) {
            $this->assertStringContainsString($path, $routes);
        }
    }

    public function test_controller_exposes_each_required_action()
    {
        $controller = new ReflectionClass(SystemUserSettingsController::class);

        foreach ([
            'users',
            'saveUser',
            'roles',
            'saveRolePermissions',
            'ipWhitelist',
            'saveIpWhitelist',
            'deleteIpWhitelist',
            'tasks',
            'runTask',
            'retryTask',
            'taskHistory',
            'exportTaskHistory',
            'logs',
            'exportLogs',
        ] as $method) {
            $this->assertTrue(
                $controller->hasMethod($method),
                "Missing controller method {$method}"
            );
        }
    }
}
