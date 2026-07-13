<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDownloadFieldsToCodePayTable extends Migration
{
    public function up()
    {
        $needsDownloadName = !Schema::hasColumn('code_pay', 'download_name');
        $needsDownloadUrl = !Schema::hasColumn('code_pay', 'download_url');

        if (!$needsDownloadName && !$needsDownloadUrl) {
            return;
        }

        Schema::table('code_pay', function (Blueprint $table) use ($needsDownloadName, $needsDownloadUrl) {
            if ($needsDownloadName) {
                $table->string('download_name')->nullable()->default(null)->comment('下载名称')->after('remark');
            }
            if ($needsDownloadUrl) {
                $table->string('download_url')->nullable()->default(null)->comment('下载地址')->after('download_name');
            }
        });
    }

    public function down()
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('code_pay', 'download_name') ? 'download_name' : null,
            Schema::hasColumn('code_pay', 'download_url') ? 'download_url' : null,
        ]));

        if (!$columns) {
            return;
        }

        Schema::table('code_pay', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
}
