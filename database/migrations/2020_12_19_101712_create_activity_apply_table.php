<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityApplyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_apply', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('activity_id')->comment('活动id');
            $table->integer('user_id')->comment('申请人');
            $table->tinyInteger('state')->default(1)->comment('1待审核 2通过 3拒绝');
            $table->dateTime('check_time')->nullable()->comment('审核时间');

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
        Schema::dropIfExists('activity_apply');
    }
}
