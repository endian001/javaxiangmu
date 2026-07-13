<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTcgBusinessOperationTables extends Migration
{
    public function up()
    {
        $this->createActivityBlacklists();
        $this->createActivityCoupons();
        $this->createActivityMultiplierRules();
        $this->createPlayerLimitRules();
        $this->createUserGameRestrictions();
    }

    public function down()
    {
        Schema::dropIfExists('tcg_user_game_restrictions');
        Schema::dropIfExists('tcg_player_limit_rules');
        Schema::dropIfExists('tcg_activity_multiplier_rules');
        Schema::dropIfExists('tcg_activity_coupons');
        Schema::dropIfExists('tcg_activity_blacklists');
    }

    private function createActivityBlacklists()
    {
        if (Schema::hasTable('tcg_activity_blacklists')) {
            return;
        }

        Schema::create('tcg_activity_blacklists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->string('reason', 255)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createActivityCoupons()
    {
        if (Schema::hasTable('tcg_activity_coupons')) {
            return;
        }

        Schema::create('tcg_activity_coupons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('coupon_code', 100)->unique();
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->string('username', 100)->nullable()->index();
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createActivityMultiplierRules()
    {
        if (Schema::hasTable('tcg_activity_multiplier_rules')) {
            return;
        }

        Schema::create('tcg_activity_multiplier_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('rule_name', 150);
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->decimal('multiplier', 10, 4)->default(1);
            $table->decimal('min_amount', 14, 4)->default(0);
            $table->decimal('max_amount', 14, 4)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createPlayerLimitRules()
    {
        if (Schema::hasTable('tcg_player_limit_rules')) {
            return;
        }

        Schema::create('tcg_player_limit_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('game_scope', 150)->default('all');
            $table->decimal('max_bet', 14, 4)->nullable();
            $table->decimal('max_payout', 14, 4)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function createUserGameRestrictions()
    {
        if (Schema::hasTable('tcg_user_game_restrictions')) {
            return;
        }

        Schema::create('tcg_user_game_restrictions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('game_scope', 150)->default('all');
            $table->string('restriction_type', 50)->default('blocked');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }
}
