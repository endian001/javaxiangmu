<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IconToActivityTypesTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('activity_types', 'icon')) {
            return;
        }

        Schema::table('activity_types', function (Blueprint $table) {
            $table->string('icon')->nullable()->comment('图标');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('activity_types', 'icon')) {
            return;
        }

        Schema::table('activity_types', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
}
