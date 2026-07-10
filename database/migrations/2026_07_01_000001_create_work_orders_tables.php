<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkOrdersTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('work_orders')) {
            Schema::create('work_orders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('order_no', 64)->unique();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('username', 120)->nullable()->index();
                $table->string('title', 200);
                $table->text('content');
                $table->string('category', 50)->default('general')->index();
                $table->string('priority', 20)->default('normal')->index();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedInteger('admin_id')->nullable()->index();
                $table->text('admin_reply')->nullable();
                $table->timestamp('admin_reply_time')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('work_order_replies')) {
            Schema::create('work_order_replies', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('work_order_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedInteger('admin_id')->nullable()->index();
                $table->text('content');
                $table->string('type', 20)->default('user')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('work_order_replies');
        Schema::dropIfExists('work_orders');
    }
}
