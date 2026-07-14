<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTcgPointMallOperationTables extends Migration
{
    public function up()
    {
        $this->createPointRules();
        $this->createPointAdjustments();
        $this->createPointMallProducts();
        $this->createPointExchangeOrders();
        $this->createPointRewardRecords();
    }

    public function down()
    {
        Schema::dropIfExists('tcg_point_reward_records');
        Schema::dropIfExists('tcg_point_exchange_orders');
        Schema::dropIfExists('tcg_point_mall_products');
        Schema::dropIfExists('tcg_point_adjustments');
        Schema::dropIfExists('tcg_point_rules');
    }

    private function createPointRules()
    {
        if (Schema::hasTable('tcg_point_rules')) {
            return;
        }

        Schema::create('tcg_point_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('rule_code', 80)->unique();
            $table->string('rule_name', 150);
            $table->string('earn_scene', 50)->index();
            $table->decimal('points_per_unit', 14, 4)->default(0);
            $table->unsignedInteger('daily_limit')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createPointAdjustments()
    {
        if (Schema::hasTable('tcg_point_adjustments')) {
            return;
        }

        Schema::create('tcg_point_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('adjust_type', 40)->index();
            $table->integer('points_delta')->default(0);
            $table->string('reason_code', 80)->nullable()->index();
            $table->string('related_order_no', 120)->nullable()->index();
            $table->string('status', 32)->default('pending')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createPointMallProducts()
    {
        if (Schema::hasTable('tcg_point_mall_products')) {
            return;
        }

        Schema::create('tcg_point_mall_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_code', 80)->unique();
            $table->string('product_name', 150);
            $table->unsignedInteger('points_price')->default(0);
            $table->unsignedInteger('stock_total')->default(0);
            $table->unsignedInteger('stock_used')->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createPointExchangeOrders()
    {
        if (Schema::hasTable('tcg_point_exchange_orders')) {
            return;
        }

        Schema::create('tcg_point_exchange_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_no', 120)->unique();
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('product_code', 80)->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('points_cost')->default(0);
            $table->text('delivery_info')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createPointRewardRecords()
    {
        if (Schema::hasTable('tcg_point_reward_records')) {
            return;
        }

        Schema::create('tcg_point_reward_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reward_no', 120)->unique();
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('reward_source', 80)->index();
            $table->unsignedInteger('points_amount')->default(0);
            $table->string('related_order_no', 120)->nullable()->index();
            $table->string('status', 32)->default('issued')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }
}
