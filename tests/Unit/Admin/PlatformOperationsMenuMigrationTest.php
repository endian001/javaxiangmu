<?php

namespace Tests\Unit\Admin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperationsMenuMigrationTest extends TestCase
{
    public function test_it_updates_only_the_first_wave_visible_menu_uris()
    {
        Schema::dropIfExists('admin_menu');
        Schema::create('admin_menu', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('uri')->nullable();
            $table->string('extension')->default('');
            $table->boolean('show')->default(true);
            $table->timestamps();
        });

        DB::table('admin_menu')->insert([
            ['id' => 28, 'parent_id' => 27, 'title' => '接口管理', 'uri' => 'apis', 'show' => 0],
            ['id' => 17, 'parent_id' => 15, 'title' => '银行类型', 'uri' => 'banks', 'show' => 0],
            ['id' => 16, 'parent_id' => 15, 'title' => '收款银行卡管理', 'uri' => 'pay-settings', 'show' => 0],
            ['id' => 38, 'parent_id' => 34, 'title' => '代理结算方案', 'uri' => 'agent-settlements', 'show' => 0],
            ['id' => 88, 'parent_id' => 84, 'title' => '游戏厂商设置', 'uri' => 'apis', 'show' => 1],
            ['id' => 91, 'parent_id' => 84, 'title' => '平台支付管理', 'uri' => 'banks', 'show' => 1],
            ['id' => 92, 'parent_id' => 84, 'title' => '支付账号设置', 'uri' => 'pay-settings', 'show' => 1],
            ['id' => 93, 'parent_id' => 84, 'title' => '代理政策设置', 'uri' => 'agent-settlements', 'show' => 1],
        ]);

        $path = database_path(
            'migrations/2026_07_10_000006_update_platform_operations_menu_uris.php'
        );
        $this->assertFileExists($path);

        require_once $path;
        (new \UpdatePlatformOperationsMenuUris())->up();

        $expected = [
            '游戏厂商设置' => 'tcg/31018',
            '平台支付管理' => 'tcg/20068',
            '支付账号设置' => 'tcg/20028',
            '代理政策设置' => 'tcg/20500',
        ];

        foreach ($expected as $title => $uri) {
            $this->assertDatabaseHas('admin_menu', [
                'title' => $title,
                'uri' => $uri,
                'show' => 1,
            ]);
        }

        foreach ([
            '接口管理' => 'apis',
            '银行类型' => 'banks',
            '收款银行卡管理' => 'pay-settings',
            '代理结算方案' => 'agent-settlements',
        ] as $title => $uri) {
            $this->assertDatabaseHas('admin_menu', [
                'title' => $title,
                'uri' => $uri,
                'show' => 0,
            ]);
        }
    }
}
