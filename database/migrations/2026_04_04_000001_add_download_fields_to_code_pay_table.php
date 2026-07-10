<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDownloadFieldsToCodePayTable extends Migration
{
    public function up()
    {
        Schema::table('code_pay', function (Blueprint $table) {
            $table->string('download_name')->nullable()->default(null)->comment('下载名称')->after('remark');
            $table->string('download_url')->nullable()->default(null)->comment('下载地址')->after('download_name');
        });
    }

    public function down()
    {
        Schema::table('code_pay', function (Blueprint $table) {
            $table->dropColumn(['download_name', 'download_url']);
        });
    }
}
