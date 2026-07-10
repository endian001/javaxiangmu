<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class GameManagementRoutesTest extends TestCase
{
    public function test_all_game_management_routes_are_explicit_and_precede_the_generic_route()
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/app/Admin/routes.php');
        $genericPosition = strpos($routes, "'/tcg/{code}'");
        $this->assertNotFalse($genericPosition);

        $routesByCode = [
            '31202' => 'winnerRankings',
            '31000' => 'thirdPartyGames',
            '70037' => 'hotGames',
            '20401' => 'lotteryBranches',
            '5000' => 'lotteryDraws',
            '5500' => 'lotterySettings',
            '5754' => 'lotteryTypes',
            '6400' => 'lotteryPlays',
            '5749' => 'lotterySalesMonitor',
            '5700' => 'lotteryBetInterference',
            '5600' => 'lotteryHotSort',
            '260025' => 'freeSpins',
        ];

        foreach ($routesByCode as $code => $method) {
            $route = "'/tcg/{$code}'";
            $position = strpos($routes, $route);
            $this->assertNotFalse($position, "Missing route {$route}");
            $this->assertLessThan($genericPosition, $position);
            $this->assertStringContainsString(
                "'GameManagementController@{$method}'",
                $routes
            );
        }

        foreach ([
            '/tcg/game-management/{code}/records',
            '/tcg/game-management/{code}/records/{id}',
            '/tcg/game-management/{code}/bulk-delete',
            '/tcg/game-management/{code}/status',
            '/tcg/game-management/{code}/import',
            '/tcg/game-management/{code}/export',
        ] as $path) {
            $this->assertStringContainsString($path, $routes);
        }
    }
}
