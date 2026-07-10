<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PlatformOperationsRoutesTest extends TestCase
{
    public function test_first_wave_routes_are_independent_and_precede_the_generic_tcg_route()
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/app/Admin/routes.php');
        $genericPosition = strpos($routes, "'/tcg/{code}'");
        $this->assertNotFalse($genericPosition);

        $routesByCode = [
            '90510' => 'siteSettings',
            '36000' => 'domainRoutes',
            '31018' => 'gameVendors',
            '90401' => 'platformFeatures',
            '24001' => 'withdrawalRisk',
            '20068' => 'paymentManagement',
            '20028' => 'paymentAccounts',
            '20500' => 'agentPolicy',
            '21150' => 'commissionSettings',
            '12650' => 'helpCenter',
            '2981' => 'smsSettings',
            '800003' => 'pilotService',
            '31001' => 'fundDetails',
            '20048' => 'bankReconciliation',
            '20032' => 'bankAccounts',
            '90040' => 'feeRecharge',
        ];

        foreach ($routesByCode as $code => $method) {
            $route = "'/tcg/{$code}'";
            $position = strpos($routes, $route);
            $this->assertNotFalse($position, "Missing route {$route}");
            $this->assertLessThan($genericPosition, $position);
            $this->assertStringContainsString(
                "'PlatformOperationsController@{$method}'",
                $routes
            );
        }

        foreach ([
            '/tcg/platform-operations/{code}/settings',
            '/tcg/platform-operations/{code}/records',
            '/tcg/platform-operations/{code}/records/{id}',
            '/tcg/platform-operations/{code}/bulk-delete',
            '/tcg/platform-operations/{code}/status',
            '/tcg/platform-operations/{code}/import',
            '/tcg/platform-operations/{code}/export',
        ] as $path) {
            $this->assertStringContainsString($path, $routes);
        }
    }
}
