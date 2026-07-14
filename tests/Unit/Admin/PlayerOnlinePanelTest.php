<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PlayerOnlinePanelTest extends TestCase
{
    public function test_online_panel_has_dedicated_route_before_generic_shell()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/routes.php';
        $this->assertFileExists($path);

        $source = file_get_contents($path);

        $this->assertStringContainsString(
            "\$router->get('/tcg/12660', 'PlayerOnlinePanelController@index');",
            $source
        );
        $this->assertLessThan(
            strpos($source, "\$router->get('/tcg/{code}', 'TcgShellController@show')"),
            strpos($source, "\$router->get('/tcg/12660', 'PlayerOnlinePanelController@index')")
        );
    }

    public function test_online_panel_controller_uses_real_member_online_sources()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Controllers/PlayerOnlinePanelController.php';
        $this->assertFileExists($path);

        $source = file_get_contents($path);

        foreach ([
            'users',
            'user_operate_logs',
            'isonline',
            'logintime',
            'lastip',
            'last_login_ip_address',
            'active15Minutes',
            'onlineAgents',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringNotContainsString('功能开发中', $source);
        $this->assertStringNotContainsString('演示数据', $source);
    }

    public function test_online_panel_view_contains_operational_metrics_and_filters()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/player-online-panel.blade.php';
        $this->assertFileExists($path);

        $source = file_get_contents($path);

        foreach ([
            '在线人数面板',
            '在线总人数',
            '在线会员',
            '在线代理',
            '今日登录',
            '近15分钟活跃',
            '玩家明细',
            '在线状态',
            '最后登录IP',
            '登录次数',
            'platform-online-panel',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }
}
