<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIconToActivityTypesTable extends Migration
{
    public function up()
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->string('icon')->nullable()->comment('图标');
        });
    }

    public function down()
    {
        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
}
