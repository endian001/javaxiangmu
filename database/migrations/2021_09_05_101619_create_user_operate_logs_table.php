<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOperateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_operate_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id')->comment('用户id');
            $table->tinyInteger('type')->comment('操作类型 1登录 2登出 3会员操作 4代理后台登入 5代理后台登出 6会员转入接口异常');
            $table->text('login_ua')->comment('登录请求头');
            $table->string('login_ip',100)->comment('登录ip');
            $table->string('ip_address',100)->comment('ip地址');
            $table->string('desc',255)->nullable()->comment('描述');
            $table->string('info',255)->nullable()->comment('备注');
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
        Schema::dropIfExists('user_operate_logs');
    }
}
