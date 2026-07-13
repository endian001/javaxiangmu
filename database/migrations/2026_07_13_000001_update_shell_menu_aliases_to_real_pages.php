<?php

use App\Admin\Support\TcgMenuAlias;
use App\Admin\Support\TcgShellCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateShellMenuAliasesToRealPages extends Migration
{
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

        $menu = TcgShellCatalog::flattenMenu();

        foreach (TcgMenuAlias::all() as $code => $target) {
            $query = DB::table($table)->where('show', 1);

            if ($rollback) {
                $title = $menu[$code]['title'] ?? null;
                if (!$title) {
                    continue;
                }
                $query->where('uri', $target)->where('title', $title);
            } else {
                $query->where('uri', 'tcg/'.$code);
            }

            $query->update([
                'uri' => $rollback ? 'tcg/'.$code : $target,
                'updated_at' => now(),
            ]);
        }
    }
}
