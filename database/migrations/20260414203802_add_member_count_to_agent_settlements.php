<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ToAgentSettlements extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('agent_settlements', 'required_new_members')) {
            return;
        }

        Schema::table('agent_settlements', function (Blueprint $table) {
            $table->integer('required_new_members')->default(0)->comment('当月新增会员数量要求');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('agent_settlements', 'required_new_members')) {
            return;
        }

        Schema::table('agent_settlements', function (Blueprint $table) {
            $table->dropColumn('required_new_members');
        });
    }
}
