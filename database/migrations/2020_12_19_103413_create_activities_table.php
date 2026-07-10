<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('type')->comment('活动类型');
            $table->string('title',50)->comment('标题');
            $table->text('content')->comment('活动内容');
            $table->integer('apply_count')->default(0)->comment('申请次数');
            $table->string('banner',255)->comment('活动图片');
            $table->tinyInteger('can_apply')->default(1)->comment('1可申请 0不可申请');
            $table->tinyInteger('state')->default(1)->comment('1正常 0禁用');
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
        Schema::dropIfExists('activities');
    }
}
