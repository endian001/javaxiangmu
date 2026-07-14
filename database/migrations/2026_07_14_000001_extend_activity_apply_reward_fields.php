<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendActivityApplyRewardFields extends Migration
{
    public function up()
    {
        Schema::table('activity_apply', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_apply', 'coupon_code')) {
                $table->string('coupon_code', 100)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('activity_apply', 'reward_amount')) {
                $table->decimal('reward_amount', 14, 4)->default(0)->after('coupon_code');
            }
            if (!Schema::hasColumn('activity_apply', 'reward_source')) {
                $table->string('reward_source', 50)->nullable()->after('reward_amount');
            }
            if (!Schema::hasColumn('activity_apply', 'issued_transfer_log_id')) {
                $table->unsignedBigInteger('issued_transfer_log_id')->nullable()->after('check_time');
            }
            if (!Schema::hasColumn('activity_apply', 'issued_at')) {
                $table->dateTime('issued_at')->nullable()->after('issued_transfer_log_id');
            }
        });
    }

    public function down()
    {
        Schema::table('activity_apply', function (Blueprint $table) {
            foreach (['issued_at', 'issued_transfer_log_id', 'reward_source', 'reward_amount', 'coupon_code'] as $column) {
                if (Schema::hasColumn('activity_apply', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
