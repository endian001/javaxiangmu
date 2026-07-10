<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class GameManagementCatalogTest extends TestCase
{
    public function test_it_exposes_all_game_management_pages_with_real_business_contracts()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Services/GameManagementService.php';
        $this->assertFileExists($path);

        require_once $path;
        $pages = (new \App\Admin\Services\GameManagementService())->pages();

        $this->assertSame([
            '31202' => '中奖排行管理',
            '31000' => '三方游戏列表',
            '70037' => '平台热门游戏',
            '20401' => '彩票分公司',
            '5000' => '彩票开奖记录',
            '5500' => '彩票基本设置',
            '5754' => '彩种基本参数',
            '6400' => '彩票玩法参数',
            '5749' => '玩法销售监控',
            '5700' => '彩票投注干扰',
            '5600' => '彩种热门排序',
            '260025' => '免费转次数',
        ], array_map(function (array $page) {
            return $page['title'];
        }, $pages));

        foreach ($pages as $code => $page) {
            $this->assertSame((string) $code, $page['code']);
            $this->assertSame('游戏管理', $page['module']);
            $this->assertNotEmpty($page['storage']);
            $this->assertGreaterThanOrEqual(2, count($page['filters']));
            $this->assertGreaterThanOrEqual(4, count($page['columns']));
            $this->assertNotEmpty($page['actions']);
            $this->assertNotEmpty($page['fields']);
            $this->assertContains('export', $page['actions']);
        }

        $this->assertSame('game_lists', $pages['31000']['storage']);
        $this->assertSame('game_lists', $pages['70037']['storage']);
        $this->assertSame('lottery_types', $pages['5600']['storage']);
        $this->assertContains('import', $pages['5000']['actions']);
        $this->assertContains('status', $pages['5700']['actions']);
    }

    public function test_it_filters_each_page_input_to_its_declared_schema()
    {
        require_once dirname(__DIR__, 3).'/app/Admin/Services/GameManagementService.php';
        $service = new \App\Admin\Services\GameManagementService();

        $this->assertSame([
            'title' => '东南亚彩',
            'branch_code' => 'SEA',
            'status' => 'enabled',
            'sort_order' => 12,
        ], $service->filterInput('20401', [
            'title' => ' 东南亚彩 ',
            'branch_code' => 'SEA',
            'status' => 'enabled',
            'sort_order' => '12',
            'password' => 'must-not-pass',
        ]));
    }
}
