<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGameManagementTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('game_winner_rankings')) {
            Schema::create('game_winner_rankings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 120);
                $table->string('game_name', 200);
                $table->decimal('amount', 18, 4)->default(0);
                $table->unsignedInteger('player_level')->default(0);
                $table->string('game_type', 50)->nullable();
                $table->dateTime('bet_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->string('data_type', 30)->default('manual');
                $table->string('status', 20)->default('enabled');
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['status', 'data_type']);
                $table->index(['username', 'bet_at']);
            });
        }

        if (!Schema::hasTable('lottery_branches')) {
            Schema::create('lottery_branches', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title', 120);
                $table->string('branch_code', 50)->unique();
                $table->string('status', 20)->default('enabled');
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['status', 'sort_order']);
            });
        }

        if (!Schema::hasTable('lottery_draw_records')) {
            Schema::create('lottery_draw_records', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('lottery_code', 80);
                $table->string('lottery_name', 160);
                $table->string('issue_no', 100);
                $table->string('draw_numbers', 255);
                $table->dateTime('draw_at')->nullable();
                $table->string('status', 20)->default('published');
                $table->string('source', 80)->default('manual');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['lottery_code', 'issue_no']);
                $table->index(['branch_id', 'draw_at']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('lottery_group_settings')) {
            Schema::create('lottery_group_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('group_code', 50)->unique();
                $table->string('group_name', 120);
                $table->decimal('max_bet_per_order', 18, 4)->default(0);
                $table->decimal('max_bet_per_issue', 18, 4)->default(0);
                $table->decimal('max_win_per_order', 18, 4)->default(0);
                $table->decimal('max_win_per_player_issue', 18, 4)->default(0);
                $table->unsignedInteger('max_multiple')->default(0);
                $table->decimal('unit_price', 12, 4)->default(1);
                $table->unsignedInteger('slider_interval')->default(0);
                $table->decimal('commission_rate', 8, 4)->default(0);
                $table->text('chip_settings')->nullable();
                $table->string('status', 20)->default('enabled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (!Schema::hasTable('lottery_types')) {
            Schema::create('lottery_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->string('group_code', 50);
                $table->string('lottery_code', 80)->unique();
                $table->string('lottery_name', 160);
                $table->string('attribute', 50)->nullable();
                $table->string('icon', 500)->nullable();
                $table->decimal('max_win_per_order', 18, 4)->default(0);
                $table->decimal('max_win_per_player_issue', 18, 4)->default(0);
                $table->decimal('max_bet_per_order', 18, 4)->default(0);
                $table->decimal('max_bet_per_issue', 18, 4)->default(0);
                $table->unsignedInteger('lock_seconds')->default(0);
                $table->unsignedTinyInteger('is_hot')->default(0);
                $table->unsignedTinyInteger('is_new')->default(0);
                $table->integer('sort_order')->default(0);
                $table->string('status', 20)->default('enabled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['branch_id', 'group_code']);
                $table->index(['is_hot', 'sort_order']);
                $table->index('status');
            });
        }

        if (!Schema::hasTable('lottery_play_settings')) {
            Schema::create('lottery_play_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('lottery_type_id');
                $table->string('play_code', 80);
                $table->string('play_name', 160);
                $table->decimal('odds', 18, 6)->default(0);
                $table->decimal('min_bet', 18, 4)->default(0);
                $table->decimal('max_bet', 18, 4)->default(0);
                $table->decimal('max_win', 18, 4)->default(0);
                $table->integer('sort_order')->default(0);
                $table->string('status', 20)->default('enabled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['lottery_type_id', 'play_code']);
                $table->index(['status', 'sort_order']);
            });
        }

        if (!Schema::hasTable('lottery_sales_controls')) {
            Schema::create('lottery_sales_controls', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('lottery_type_id');
                $table->string('play_code', 80)->nullable();
                $table->decimal('stock_amount', 18, 4)->default(0);
                $table->decimal('payout_adjustment', 10, 6)->default(0);
                $table->unsignedTinyInteger('bet_level_sort')->default(0);
                $table->string('mode', 30)->default('casino');
                $table->string('status', 20)->default('enabled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['lottery_type_id', 'status']);
            });
        }

        if (!Schema::hasTable('lottery_bet_interferences')) {
            Schema::create('lottery_bet_interferences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('username', 120);
                $table->unsignedBigInteger('lottery_type_id')->nullable();
                $table->string('play_code', 80)->nullable();
                $table->string('interference_type', 50);
                $table->decimal('interference_value', 18, 6)->default(0);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->text('reason')->nullable();
                $table->string('status', 20)->default('enabled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['username', 'status']);
                $table->index(['lottery_type_id', 'status']);
            });
        }

        if (!Schema::hasTable('free_spin_records')) {
            Schema::create('free_spin_records', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('stat_month', 7);
                $table->string('vendor_code', 80);
                $table->string('plan_id', 120);
                $table->unsignedBigInteger('available_spins')->default(0);
                $table->unsignedBigInteger('used_total_spins')->default(0);
                $table->unsignedBigInteger('used_free_spins')->default(0);
                $table->unsignedBigInteger('used_paid_spins')->default(0);
                $table->decimal('win_amount', 18, 4)->default(0);
                $table->string('status', 20)->default('enabled');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['stat_month', 'vendor_code', 'plan_id']);
                $table->index('status');
            });
        }

        if (Schema::hasTable('admin_permissions')) {
            $now = date('Y-m-d H:i:s');
            $permissions = [
                'game.management.read' => '游戏管理-查看',
                'game.management.write' => '游戏管理-新增编辑状态',
                'game.management.delete' => '游戏管理-删除',
                'game.management.export' => '游戏管理-导出',
            ];
            $order = 700;
            foreach ($permissions as $slug => $name) {
                DB::table('admin_permissions')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'http_method' => '',
                        'http_path' => null,
                        'order' => $order++,
                        'parent_id' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('admin_permissions')) {
            DB::table('admin_permissions')
                ->whereIn('slug', [
                    'game.management.read',
                    'game.management.write',
                    'game.management.delete',
                    'game.management.export',
                ])
                ->delete();
        }
        Schema::dropIfExists('free_spin_records');
        Schema::dropIfExists('lottery_bet_interferences');
        Schema::dropIfExists('lottery_sales_controls');
        Schema::dropIfExists('lottery_play_settings');
        Schema::dropIfExists('lottery_types');
        Schema::dropIfExists('lottery_group_settings');
        Schema::dropIfExists('lottery_draw_records');
        Schema::dropIfExists('lottery_branches');
        Schema::dropIfExists('game_winner_rankings');
    }
}
