<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->comment('支付类型名称');
            $table->string('icon')->nullable()->comment('图标');
            $table->tinyInteger('state')->default(1)->comment('1可用 0禁用');
            $table->integer('sort_order')->default(0)->comment('排序，数字越小越靠前');
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
        Schema::dropIfExists('pay_types');
    }
}
