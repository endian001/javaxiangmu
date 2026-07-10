<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('api_code',50)->comment('api代码');
            $table->string('api_name',100)->comment('api名称');
            $table->decimal('api_money',10,2)->default(0)->comment('api余额');
            $table->string('game_type')->nullable()->comment('游戏类型');
            $table->string('plat_type')->nullable()->comment('平台类型');
            $table->tinyInteger('state')->default(1)->comment('网站状态');
            $table->tinyInteger('app_state')->default(1)->comment('app状态');
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
        Schema::dropIfExists('apis');
    }
}
