<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserVipTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_vip', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('vipname',50)->comment('等级名称');
            $table->tinyInteger('viptype')->default(1)->comment('反水类型');
            $table->decimal('realperson',6,2)->default(0)->comment('真人');
            $table->decimal('electron',6,2)->default(0)->comment('电子');
            $table->decimal('joker',6,2)->default(0)->comment('棋牌');
            $table->decimal('sport',6,2)->default(0)->comment('体育');
            $table->decimal('fish',6,2)->default(0)->comment('捕鱼');
            $table->decimal('lottery',6,2)->default(0)->comment('彩票');
            $table->decimal('e_sport',6,2)->default(0)->comment('电竞');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->integer('exp')->default(0)->comment('经验');
            $table->tinyInteger('is_default')->default(0)->comment('是否默认');
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
        Schema::dropIfExists('user_vip');
    }
}
