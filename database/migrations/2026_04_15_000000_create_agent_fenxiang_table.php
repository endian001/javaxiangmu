<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentFenxiangTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_fenxiang', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->comment('分享标题');
            $table->string('description', 255)->comment('分享描述');
            $table->string('share_type', 50)->comment('分享类型');
            $table->text('content')->comment('分享内容');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('state')->default(1)->comment('状态：1=启用，0=禁用');
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
        Schema::dropIfExists('agent_fenxiang');
    }
}