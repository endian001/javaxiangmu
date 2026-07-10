<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaySettingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_setting', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('bank_id');
            $table->string('bank_no',100)->comment('卡号');
            $table->string('bank_owner',100)->comment('持卡人姓名');
            $table->string('bank_address',100)->comment('支行信息');
            $table->string('info',255)->nullable()->comment('备注');
            $table->tinyInteger('state')->default(1)->comment('1可用 0禁用');
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
        Schema::dropIfExists('pay_setting');
    }
}
