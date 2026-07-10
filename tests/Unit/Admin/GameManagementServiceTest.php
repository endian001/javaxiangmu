<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\GameManagementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameManagementServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'game_lists',
            'users',
            'game_winner_rankings',
            'lottery_branches',
            'lottery_draw_records',
            'lottery_group_settings',
            'lottery_types',
            'lottery_play_settings',
            'lottery_sales_controls',
            'lottery_bet_interferences',
            'free_spin_records',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('game_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform_name');
            $table->string('name');
            $table->string('game_code');
            $table->string('category_id')->nullable();
            $table->integer('order_by')->default(0);
            $table->integer('is_hot')->default(0);
            $table->integer('is_new')->default(0);
            $table->integer('is_recommend')->default(0);
            $table->integer('is_top')->default(1);
            $table->integer('site_state')->default(1);
            $table->integer('app_state')->default(1);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->integer('vip')->default(0);
            $table->integer('status')->default(1);
        });

        require_once database_path(
            'migrations/2026_07_10_000007_create_game_management_tables.php'
        );
        (new \CreateGameManagementTables())->up();
    }

    public function test_third_party_and_hot_pages_use_existing_game_lists()
    {
        DB::table('game_lists')->insert([
            [
                'platform_name' => 'PG',
                'name' => 'Fortune Tiger',
                'game_code' => 'PG001',
                'category_id' => 'slot',
                'order_by' => 2,
                'is_hot' => 1,
                'is_new' => 0,
                'is_recommend' => 1,
                'is_top' => 1,
                'site_state' => 1,
                'app_state' => 1,
            ],
            [
                'platform_name' => 'JILI',
                'name' => 'Boxing King',
                'game_code' => 'JL001',
                'category_id' => 'slot',
                'order_by' => 8,
                'is_hot' => 0,
                'is_new' => 1,
                'is_recommend' => 0,
                'is_top' => 1,
                'site_state' => 1,
                'app_state' => 1,
            ],
        ]);

        $service = new GameManagementService();
        $pgRows = $service->rows('31000', ['platform_name' => 'PG']);
        $this->assertSame(1, $pgRows->total());
        $this->assertSame('Fortune Tiger', $pgRows->items()[0]->name);

        $hotRows = $service->rows('70037');
        $this->assertSame(1, $hotRows->total());
        $this->assertSame('PG001', $hotRows->items()[0]->game_code);

        $id = DB::table('game_lists')->where('game_code', 'JL001')->value('id');
        $service->saveRecord('70037', [
            'is_hot' => 1,
            'is_recommend' => 1,
            'order_by' => 1,
        ], $id);

        $this->assertSame(1, (int) DB::table('game_lists')
            ->where('id', $id)
            ->value('is_hot'));
        $this->assertSame(1, (int) DB::table('game_lists')
            ->where('id', $id)
            ->value('order_by'));
    }

    public function test_dedicated_pages_support_create_filter_status_and_delete()
    {
        $service = new GameManagementService();
        $saved = $service->saveRecord('20401', [
            'title' => '东南亚彩',
            'branch_code' => 'SEA',
            'status' => 'enabled',
            'sort_order' => 10,
        ]);

        $this->assertSame('lottery_branches', $saved['source_table']);
        $this->assertSame(1, $service->rows('20401', ['branch_code' => 'SEA'])->total());

        $this->assertSame(1, $service->changeStatus(
            '20401',
            [$saved['id']],
            'disabled'
        ));
        $this->assertSame('disabled', DB::table('lottery_branches')
            ->where('id', $saved['id'])
            ->value('status'));

        $this->assertSame(1, $service->deleteRecord('20401', $saved['id']));
        $this->assertSame(0, DB::table('lottery_branches')->count());
    }

    public function test_lottery_type_sales_interference_and_free_spin_rows_persist()
    {
        $service = new GameManagementService();
        $branch = $service->saveRecord('20401', [
            'title' => '东南亚彩',
            'branch_code' => 'SEA',
            'status' => 'enabled',
            'sort_order' => 1,
        ]);
        $lottery = $service->saveRecord('5754', [
            'branch_id' => $branch['id'],
            'group_code' => 'SEA',
            'lottery_code' => 'TCG5D',
            'lottery_name' => '5D Lotre',
            'attribute' => 'official',
            'max_win_per_order' => 10000,
            'max_win_per_player_issue' => 20000,
            'max_bet_per_order' => 5000,
            'max_bet_per_issue' => 10000,
            'lock_seconds' => 30,
            'is_hot' => 1,
            'is_new' => 0,
            'sort_order' => 3,
            'status' => 'enabled',
        ]);
        $service->saveRecord('5749', [
            'lottery_type_id' => $lottery['id'],
            'play_code' => 'STRAIGHT',
            'stock_amount' => 8000,
            'payout_adjustment' => 0.01,
            'bet_level_sort' => 1,
            'mode' => 'casino',
            'status' => 'enabled',
        ]);

        DB::table('users')->insert([
            'username' => 'player001',
            'vip' => 2,
            'status' => 1,
        ]);
        $interference = $service->saveRecord('5700', [
            'username' => 'player001',
            'lottery_type_id' => $lottery['id'],
            'play_code' => 'STRAIGHT',
            'interference_type' => 'odds',
            'interference_value' => -0.05,
            'starts_at' => '2026-07-10 00:00:00',
            'ends_at' => '2026-07-31 23:59:59',
            'reason' => 'risk review',
            'status' => 'enabled',
        ]);
        $service->saveRecord('260025', [
            'stat_month' => '2026-07',
            'vendor_code' => 'PG',
            'plan_id' => 'FREE-001',
            'available_spins' => 100,
            'used_total_spins' => 10,
            'used_free_spins' => 8,
            'used_paid_spins' => 2,
            'win_amount' => 88.5,
            'status' => 'enabled',
        ]);

        $this->assertSame(1, DB::table('lottery_sales_controls')->count());
        $this->assertSame(
            DB::table('users')->where('username', 'player001')->value('id'),
            DB::table('lottery_bet_interferences')
                ->where('id', $interference['id'])
                ->value('user_id')
        );
        $this->assertSame(1, $service->rows('260025', [
            'stat_month' => '2026-07',
            'vendor_code' => 'PG',
        ])->total());
    }

    public function test_export_reads_each_filtered_row_once_and_integer_status_updates()
    {
        $rows = [];
        for ($index = 1; $index <= 130; $index++) {
            $rows[] = [
                'platform_name' => 'PG',
                'name' => 'Game '.$index,
                'game_code' => 'PG'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'category_id' => 'slot',
                'order_by' => $index,
                'is_hot' => $index <= 5 ? 1 : 0,
                'is_new' => 0,
                'is_recommend' => 0,
                'is_top' => 1,
                'site_state' => 1,
                'app_state' => 1,
            ];
        }
        foreach (array_chunk($rows, 40) as $chunk) {
            DB::table('game_lists')->insert($chunk);
        }

        $service = new GameManagementService();
        $exported = $service->exportRows('31000', ['platform_name' => 'PG']);
        $codes = array_map(function ($row) {
            return $row->game_code;
        }, $exported);

        $this->assertCount(130, $exported);
        $this->assertCount(130, array_unique($codes));

        $id = DB::table('game_lists')->where('game_code', 'PG0001')->value('id');
        $this->assertSame(1, $service->changeStatus(
            '31000',
            [$id],
            'disabled'
        ));
        $this->assertSame(0, (int) DB::table('game_lists')
            ->where('id', $id)
            ->value('site_state'));
    }
}
