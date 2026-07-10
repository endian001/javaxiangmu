<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class KycSettingsRoutesTest extends TestCase
{
    public function test_routes_are_registered_before_the_generic_tcg_route()
    {
        $routes = file_get_contents(
            dirname(__DIR__, 3).'/app/Admin/routes.php'
        );

        $this->assertStringContainsString(
            "\$router->get('/tcg/610110', 'KycSettingsController@fields');",
            $routes
        );
        $this->assertStringContainsString(
            "\$router->get('/tcg/290000', 'KycSettingsController@rules');",
            $routes
        );
        $this->assertStringContainsString(
            "\$router->get('/tcg/290004', 'KycSettingsController@content');",
            $routes
        );
        $this->assertStringContainsString(
            "\$router->post('/tcg/kyc/upload', 'KycSettingsController@upload');",
            $routes
        );

        $this->assertLessThan(
            strpos($routes, "\$router->get('/tcg/{code}'"),
            strpos($routes, "\$router->get('/tcg/610110'")
        );
    }
}
