<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixLegacyAdminMenuDeadLinks extends Migration
{
    public function up()
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        if (!Schema::hasTable($table)) {
            return;
        }

        $now = now();

        DB::table($table)
            ->where('title', '内容管理')
            ->where('uri', '/admin/activities')
            ->update([
                'uri' => null,
                'updated_at' => $now,
            ]);

        $sponsorUpdate = [
            'uri' => null,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn($table, 'show')) {
            $sponsorUpdate['show'] = 0;
        }

        DB::table($table)
            ->where('title', '赞助管理')
            ->where('uri', 'sponsors')
            ->update($sponsorUpdate);
    }

    public function down()
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        if (!Schema::hasTable($table)) {
            return;
        }

        $now = now();

        DB::table($table)
            ->where('title', '内容管理')
            ->whereNull('uri')
            ->update([
                'uri' => '/admin/activities',
                'updated_at' => $now,
            ]);

        $sponsorUpdate = [
            'uri' => 'sponsors',
            'updated_at' => $now,
        ];

        if (Schema::hasColumn($table, 'show')) {
            $sponsorUpdate['show'] = 1;
        }

        DB::table($table)
            ->where('title', '赞助管理')
            ->whereNull('uri')
            ->update($sponsorUpdate);
    }
}
