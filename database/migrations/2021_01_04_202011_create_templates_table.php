<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',50)->comment('模板名称');
            $table->string('pic',255)->comment('缩略图');
            $table->tinyInteger('client_type')->default(1)->comment('1pc 2wap 3app');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('template_id',50)->comment('模板标识');
            $table->tinyInteger('state')->default(1)->comment('1可用 0禁用 2正在使用');
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
        Schema::dropIfExists('templates');
    }
}
