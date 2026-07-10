<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayTypeIdToCodePayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('code_pay', function (Blueprint $table) {
            $table->unsignedBigInteger('pay_type_id')->nullable()->comment('支付类型ID')->after('id');
            $table->foreign('pay_type_id')->references('id')->on('pay_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('code_pay', function (Blueprint $table) {
            $table->dropForeign(['pay_type_id']);
            $table->dropColumn('pay_type_id');
        });
    }
}
