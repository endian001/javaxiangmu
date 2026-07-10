<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('type')->default(1)->comment('1pc 2移动端');
            $table->string('title',100)->nullable()->comment('标题');
            $table->string('pic',255)->comment('图片地址');
            $table->string('jump_url',255)->nullable()->comment('跳转链接');
            $table->integer('order')->default(0)->comment('排序');
            $table->tinyInteger('state')->default(1)->comment('1显示 0禁用');
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
        Schema::dropIfExists('banners');
    }
}
