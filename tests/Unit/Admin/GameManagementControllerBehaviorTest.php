<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\GameManagementController;
use App\Admin\Services\GameManagementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GameManagementControllerBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.permission.enable' => false]);

        foreach ([
            'game_lists',
            'admin_audit_logs',
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
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('admin_name', 120)->nullable();
            $table->string('action', 100);
            $table->string('module', 100);
            $table->text('content');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('context')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        require_once database_path(
            'migrations/2026_07_10_000007_create_game_management_tables.php'
        );
        (new \CreateGameManagementTables())->up();
    }

    public function test_record_can_be_created_exported_disabled_and_deleted_with_audit()
    {
        $create = $this->request('POST', [
            'title' => '南亚彩',
            'branch_code' => 'SA',
            'status' => 'enabled',
            'sort_order' => 5,
        ]);
        $payload = $this->controller()
            ->saveRecord($create, '20401')
            ->getData(true);

        $this->assertTrue($payload['status']);
        $id = $payload['data']['id'];
        $this->assertSame('南亚彩', DB::table('lottery_branches')
            ->where('id', $id)
            ->value('title'));

        $csv = $this->controller()
            ->export($this->request('GET', ['branch_code' => 'SA']), '20401')
            ->getContent();
        $this->assertStringContainsString('南亚彩', $csv);

        $status = $this->controller()->changeStatus(
            $this->request('POST', ['ids' => [$id], 'status' => 'disabled']),
            '20401'
        )->getData(true);
        $this->assertTrue($status['status']);

        $deleted = $this->controller()
            ->deleteRecord($this->request('DELETE'), '20401', $id)
            ->getData(true);
        $this->assertTrue($deleted['status']);
        $this->assertSame(0, DB::table('lottery_branches')->count());

        $this->assertSame([
            'game_management.record.create',
            'game_management.export',
            'game_management.status.update',
            'game_management.record.delete',
        ], DB::table('admin_audit_logs')->orderBy('id')->pluck('action')->all());
    }

    protected function controller()
    {
        return new GameManagementController(new GameManagementService());
    }

    protected function request($method, array $input = [])
    {
        $request = Request::create('/game/test', $method, $input);
        $request->headers->set('User-Agent', 'PHPUnit');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
    }
}
