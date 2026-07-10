<?php

namespace Tests\Unit\Admin;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameManagementMigrationTest extends TestCase
{
    public function test_it_creates_dedicated_game_and_lottery_tables()
    {
        $path = database_path(
            'migrations/2026_07_10_000007_create_game_management_tables.php'
        );
        $this->assertFileExists($path);

        require_once $path;
        (new \CreateGameManagementTables())->up();

        $expected = [
            'game_winner_rankings' => [
                'username', 'game_name', 'amount', 'player_level', 'game_type',
                'bet_at', 'ended_at', 'data_type', 'status', 'sort_order',
            ],
            'lottery_branches' => [
                'title', 'branch_code', 'status', 'sort_order',
            ],
            'lottery_draw_records' => [
                'branch_id', 'lottery_code', 'lottery_name', 'issue_no',
                'draw_numbers', 'draw_at', 'status', 'source',
            ],
            'lottery_group_settings' => [
                'group_code', 'group_name', 'max_bet_per_order',
                'max_bet_per_issue', 'max_win_per_order',
                'max_win_per_player_issue', 'max_multiple', 'unit_price',
                'slider_interval', 'commission_rate', 'status',
            ],
            'lottery_types' => [
                'branch_id', 'group_code', 'lottery_code', 'lottery_name',
                'attribute', 'icon', 'max_win_per_order',
                'max_win_per_player_issue', 'max_bet_per_order',
                'max_bet_per_issue', 'lock_seconds', 'is_hot', 'is_new',
                'sort_order', 'status',
            ],
            'lottery_play_settings' => [
                'lottery_type_id', 'play_code', 'play_name', 'odds',
                'min_bet', 'max_bet', 'max_win', 'sort_order', 'status',
            ],
            'lottery_sales_controls' => [
                'lottery_type_id', 'play_code', 'stock_amount',
                'payout_adjustment', 'bet_level_sort', 'mode', 'status',
            ],
            'lottery_bet_interferences' => [
                'user_id', 'username', 'lottery_type_id', 'play_code',
                'interference_type', 'interference_value', 'starts_at',
                'ends_at', 'reason', 'status',
            ],
            'free_spin_records' => [
                'stat_month', 'vendor_code', 'plan_id', 'available_spins',
                'used_total_spins', 'used_free_spins', 'used_paid_spins',
                'win_amount', 'status',
            ],
        ];

        foreach ($expected as $table => $columns) {
            $this->assertTrue(Schema::hasColumns($table, $columns), $table);
        }
    }

    public function test_it_registers_assignable_game_management_permissions()
    {
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('http_method')->nullable();
            $table->text('http_path')->nullable();
            $table->integer('order')->default(0);
            $table->integer('parent_id')->default(0);
            $table->timestamps();
        });

        require_once database_path(
            'migrations/2026_07_10_000007_create_game_management_tables.php'
        );
        (new \CreateGameManagementTables())->up();

        $this->assertEqualsCanonicalizing([
            'game.management.read',
            'game.management.write',
            'game.management.delete',
            'game.management.export',
        ], DB::table('admin_permissions')
            ->where('slug', 'like', 'game.management.%')
            ->pluck('slug')
            ->all());
    }
}
