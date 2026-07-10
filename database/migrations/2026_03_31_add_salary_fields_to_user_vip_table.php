<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalaryFieldsToUserVipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_vip', function (Blueprint $table) {
            $table->decimal('upgrade_bonus', 10, 2)->default(0)->comment('升级奖励金额')->after('is_default');
            $table->decimal('weekly_salary', 10, 2)->default(0)->comment('周俸禄金额')->after('upgrade_bonus');
            $table->decimal('monthly_salary', 10, 2)->default(0)->comment('月俸禄金额')->after('weekly_salary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_vip', function (Blueprint $table) {
            $table->dropColumn(['upgrade_bonus', 'weekly_salary', 'monthly_salary']);
        });
    }
}
