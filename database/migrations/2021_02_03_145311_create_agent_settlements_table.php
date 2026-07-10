<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentSettlementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_settlements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',100)->comment('方案名称');
            $table->tinyInteger('type')->default(1)->comment('结算类型 1返点 2返佣');
            $table->decimal('realperson',6,2)->default(0)->comment('真人');
            $table->decimal('electron',6,2)->default(0)->comment('电子');
            $table->decimal('joker',6,2)->default(0)->comment('棋牌');
            $table->decimal('sport',6,2)->default(0)->comment('体育');
            $table->decimal('fish',6,2)->default(0)->comment('捕鱼');
            $table->decimal('lottery',6,2)->default(0)->comment('彩票');
            $table->decimal('e_sport',6,2)->default(0)->comment('电竞');
            $table->decimal('member_fs',8,2)->default(0)->comment('会员返水');
            $table->tinyInteger('state')->default(1)->comment('状态');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_settlements');
    }
}
