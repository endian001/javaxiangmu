<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdatePlatformOperationsMenuUris extends Migration
{
    private const MENU_URIS = [
        '游戏厂商设置' => ['apis', 'tcg/31018'],
        '平台支付管理' => ['banks', 'tcg/20068'],
        '支付账号设置' => ['pay-settings', 'tcg/20028'],
        '代理政策设置' => ['agent-settlements', 'tcg/20500'],
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
