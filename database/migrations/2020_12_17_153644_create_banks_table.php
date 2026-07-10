<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code','50')->nullable()->comment('银行代码');
            $table->string('bank_name','100')->comment('银行名称');
            $table->integer('order')->default(0)->comment('排序');
            $table->decimal('max_amount',8,2)->default(0)->comment('最大限额');
            $table->string('bank_img',255)->nullable()->comment('图片');
            $table->tinyInteger('state')->default(1)->comment('状态');
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
        Schema::dropIfExists('banks');
    }
}
