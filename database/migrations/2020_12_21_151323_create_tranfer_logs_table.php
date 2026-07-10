<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTranferLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_no',100)->comment('订单号');
            $table->integer('api_type')->comment('api账户类型');
            $table->integer('user_id')->comment('用户id');
            $table->tinyInteger('transfer_type')->comment('0 转入游戏 1转出游戏');
            $table->decimal('money',10,2)->default(0)->comment('转换金额');
            $table->decimal('cash_fee',8,2)->default(0)->comment('手续费');
            $table->decimal('real_money',10,2)->default(0)->comment('实到金额');
            $table->decimal('before_money',10,2)->default(0)->comment('转换前余额');
            $table->decimal('after_money',10,2)->default(0)->comment('转换后金额');
            $table->tinyInteger('state')->default(1)->comment('1成功 0失败');
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
        Schema::dropIfExists('tranfer_logs');
    }
}
