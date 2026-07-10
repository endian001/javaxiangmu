<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRechargeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recharge', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_no',200)->nullable()->comment('系统订单号');
            $table->string('out_trade_no',200)->comment('商户订单号');
            $table->integer('user_id')->comment('用户id');
            $table->decimal('amount',10,2)->comment('金额');
            $table->decimal('cash_fee',8,2)->comment('手续费');
            $table->decimal('real_money',10,2)->comment('实到金额');
            $table->tinyInteger('pay_way')->comment('1银行卡转账 2zgpay');
            $table->string('bank',100)->nullable()->comment('开户行');
            $table->string('bank_no',100)->nullable()->comment('卡号');
            $table->string('bank_address',100)->nullable()->comment('开户行');
            $table->string('bank_owner',100)->nullable()->comment('持卡人姓名');
            $table->string('info',255)->nullable()->comment('备注');
            $table->tinyInteger('state')->default(1)->comment('1待审核/支付 2支付成功/审核通过 3支付失败/审核拒绝');

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
        Schema::dropIfExists('recharge');
    }
}
