<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->comment('用户id');
            $table->string('username',50)->comment('用户名');
            $table->string('bet_id',100)->comment('注单id');
            $table->dateTime('bet_time')->comment('下注时间');
            $table->string('platform_type',20)->comment('平台');
            $table->string('game_type',20)->comment('游戏类型');
            $table->decimal('bet_amount',10,2)->default(0)->comment('下注金额');
            $table->decimal('valid_amount',10,2)->default(0)->comment('有效投注金额');
            $table->decimal('win_loss',10,2)->default(0)->comment('输赢金额');
            $table->tinyInteger('is_back')->default(0)->comment('1已反水 0未反水');
            $table->tinyInteger('status')->default(0)->comment('状态 1已结算 2未结算 0无效注单');
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
        Schema::dropIfExists('game_records');
    }
}
