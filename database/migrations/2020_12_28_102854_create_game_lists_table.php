<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('platform_name',50)->comment('平台名称');
            $table->string('name',100)->comment('游戏名称');
            $table->string('name_en',200)->comment('游戏英文名称');
            $table->string('keywords',100)->nullable()->comment('关键词');
            $table->integer('category_id')->comment('分类id');
            $table->integer('order_by')->default(0)->comment('排序');
            $table->integer('is_hot')->default(0)->comment('1热门游戏 0不是');
            $table->integer('is_new')->default(0)->comment('1最新游戏 0不是');
            $table->integer('is_recommend')->default(0)->comment('1推荐游戏 0不是');
            $table->integer('is_pc')->default(1)->comment('1pc显示 0不是');
            $table->integer('is_mobile')->default(1)->comment('1手机展示 0不是');
            $table->tinyInteger('site_state')->default(1)->comment('网站状态');
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
        Schema::dropIfExists('game_lists');
    }
}
