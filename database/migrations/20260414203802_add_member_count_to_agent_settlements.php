<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMemberCountToAgentSettlements extends Migration
{
    public function up()
    {
        Schema::table('agent_settlements', function (Blueprint $table) {
            $table->integer('required_new_members')->default(0)->comment('当月新增会员数量要求');
        });
    }

    public function down()
    {
        Schema::table('agent_settlements', function (Blueprint $table) {
            $table->dropColumn('required_new_members');
        });
    }
}
