<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('fid')->default(0)->comment('上级账号');
            $table->string('username',100)->comment('会员账号');
            $table->string('password',255)->comment('密码');
            $table->string('realname',50)->comment('真实姓名');
            $table->integer('vip')->default(0)->comment('VIP级别');
            $table->integer('level')->default(0)->comment('等级');
            $table->integer('exp')->default(0)->comment('经验值');
            $table->string('paypwd',255)->nullable()->comment('支付密码');
            $table->integer('isonline')->default(0)->comment('是否在线');
            $table->integer('allowagent')->default(0)->comment('是否允许发展下级代理');
            $table->decimal('balance',10,2)->default(0)->comment('余额');
            $table->decimal('mbalance',10,2)->default(0)->comment('码量余额');
            $table->string('phone',50)->nullable()->comment('手机');
            $table->string('mail',50)->nullable()->comment('邮箱');
            $table->decimal('paysum',10,2)->default(0)->comment('累计充值');
            $table->tinyInteger('status')->default(1)->comment('状态');
            $table->tinyInteger('isdel')->default(0)->comment('是否已删除');
            $table->tinyInteger('isblack')->default(0)->comment('是否在黑名单');
            $table->string('lastip',50)->nullable()->comment('最后登录IP');
            $table->integer('logintime')->nullable()->comment('最后登录时间');
            $table->string('sourceurl',255)->nullable()->comment('来源');
            $table->integer('loginsum')->default(0)->comment('登录次数');
            $table->date('birthday')->nullable()->comment('出生日期');
            $table->softDeletes();
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
        Schema::dropIfExists('users');
    }
}
