<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SalaryFieldsToUserVipTable extends Migration
{
    public function up()
    {
        $needsUpgradeBonus = !Schema::hasColumn('user_vip', 'upgrade_bonus');
        $needsWeeklySalary = !Schema::hasColumn('user_vip', 'weekly_salary');
        $needsMonthlySalary = !Schema::hasColumn('user_vip', 'monthly_salary');

        if (!$needsUpgradeBonus && !$needsWeeklySalary && !$needsMonthlySalary) {
            return;
        }

        Schema::table('user_vip', function (Blueprint $table) use ($needsUpgradeBonus, $needsWeeklySalary, $needsMonthlySalary) {
            if ($needsUpgradeBonus) {
                $table->decimal('upgrade_bonus', 10, 2)->default(0)->comment('升级奖励金额')->after('is_default');
            }
            if ($needsWeeklySalary) {
                $table->decimal('weekly_salary', 10, 2)->default(0)->comment('周俸禄金额')->after('upgrade_bonus');
            }
            if ($needsMonthlySalary) {
                $table->decimal('monthly_salary', 10, 2)->default(0)->comment('月俸禄金额')->after('weekly_salary');
            }
        });
    }

    public function down()
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('user_vip', 'upgrade_bonus') ? 'upgrade_bonus' : null,
            Schema::hasColumn('user_vip', 'weekly_salary') ? 'weekly_salary' : null,
            Schema::hasColumn('user_vip', 'monthly_salary') ? 'monthly_salary' : null,
        ]));

        if (!$columns) {
            return;
        }

        Schema::table('user_vip', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
}
