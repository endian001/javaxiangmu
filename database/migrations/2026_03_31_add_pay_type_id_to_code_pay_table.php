<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PayTypeIdToCodePayTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pay_types')) {
            Schema::create('pay_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 50)->comment('支付类型名称');
                $table->string('icon')->nullable()->comment('图标');
                $table->tinyInteger('state')->default(1)->comment('1可用 0禁用');
                $table->integer('sort_order')->default(0)->comment('排序，数字越小越靠前');
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('code_pay', 'pay_type_id')) {
            return;
        }

        Schema::table('code_pay', function (Blueprint $table) {
            $table->unsignedBigInteger('pay_type_id')->nullable()->comment('支付类型ID')->after('id');
            $table->foreign('pay_type_id')->references('id')->on('pay_types')->onDelete('set null');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('code_pay', 'pay_type_id')) {
            return;
        }

        Schema::table('code_pay', function (Blueprint $table) {
            try {
                $table->dropForeign(['pay_type_id']);
            } catch (Throwable $exception) {
                // Older installs may not have created the foreign key.
            }
            $table->dropColumn('pay_type_id');
        });
    }
}
