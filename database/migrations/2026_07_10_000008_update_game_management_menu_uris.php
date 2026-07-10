<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateGameManagementMenuUris extends Migration
{
    private const MENU_URIS = [
        '三方游戏列表' => ['game-lists', 'tcg/31000'],
        '平台热门游戏' => ['game-lists-app', 'tcg/70037'],
    ];

    public function up()
    {
        $this->updateUris(false);
    }

    public function down()
    {
        $this->updateUris(true);
    }

    private function updateUris(bool $rollback): void
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach (self::MENU_URIS as $title => [$oldUri, $newUri]) {
            DB::table($table)
                ->where('title', $title)
                ->where('uri', $rollback ? $newUri : $oldUri)
                ->where('show', 1)
                ->update([
                    'uri' => $rollback ? $oldUri : $newUri,
                    'updated_at' => now(),
                ]);
        }
    }
}
