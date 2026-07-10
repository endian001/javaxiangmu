<?php

namespace Tests\Unit\Admin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameManagementMenuMigrationTest extends TestCase
{
    public function test_it_updates_only_visible_game_management_menu_uris_and_can_roll_back()
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
            [
                'id' => 117,
                'parent_id' => 115,
                'title' => '三方游戏列表',
                'uri' => 'game-lists',
                'show' => 1,
            ],
            [
                'id' => 118,
                'parent_id' => 115,
                'title' => '平台热门游戏',
                'uri' => 'game-lists-app',
                'show' => 1,
            ],
            [
                'id' => 217,
                'parent_id' => 215,
                'title' => '三方游戏列表',
                'uri' => 'game-lists',
                'show' => 0,
            ],
            [
                'id' => 218,
                'parent_id' => 215,
                'title' => '旧平台热门游戏',
                'uri' => 'game-lists-app',
                'show' => 1,
            ],
        ]);

        $path = database_path(
            'migrations/2026_07_10_000008_update_game_management_menu_uris.php'
        );
        $this->assertFileExists($path);

        require_once $path;
        $migration = new \UpdateGameManagementMenuUris();
        $migration->up();

        $this->assertDatabaseHas('admin_menu', [
            'id' => 117,
            'uri' => 'tcg/31000',
            'show' => 1,
        ]);
        $this->assertDatabaseHas('admin_menu', [
            'id' => 118,
            'uri' => 'tcg/70037',
            'show' => 1,
        ]);
        $this->assertDatabaseHas('admin_menu', [
            'id' => 217,
            'uri' => 'game-lists',
            'show' => 0,
        ]);
        $this->assertDatabaseHas('admin_menu', [
            'id' => 218,
            'uri' => 'game-lists-app',
            'show' => 1,
        ]);

        $migration->down();

        $this->assertDatabaseHas('admin_menu', [
            'id' => 117,
            'uri' => 'game-lists',
            'show' => 1,
        ]);
        $this->assertDatabaseHas('admin_menu', [
            'id' => 118,
            'uri' => 'game-lists-app',
            'show' => 1,
        ]);
    }
}
