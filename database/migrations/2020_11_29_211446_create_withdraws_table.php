<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_no',100)->comment('订单号');
            $table->integer('card_id')->comment('银行卡id');
            $table->integer('user_id')->comment('用户id');
            $table->decimal('amount',10,2)->default(0)->comment('提现金额');
            $table->decimal('cash_fee',8,2)->default(0)->comment('手续费');
            $table->decimal('real_money',10,2)->default(0)->comment('实到金额');
            $table->string('info',255)->nullable()->comment('备注');
            $table->tinyInteger('state')->default(1)->comment('1待审核 2通过 3拒绝');
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
        Schema::dropIfExists('withdraws');
    }
}
