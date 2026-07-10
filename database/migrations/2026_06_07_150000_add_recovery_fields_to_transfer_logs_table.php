<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRecoveryFieldsToTransferLogsTable extends Migration
{
    public function up()
    {
        Schema::table('transfer_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('transfer_logs', 'recovery_key')) {
                $table->string('recovery_key', 120)->nullable()->after('remark')->comment('回收幂等键');
            }

            if (!Schema::hasColumn('transfer_logs', 'recovery_status')) {
                $table->string('recovery_status', 50)->nullable()->after('recovery_key')->comment('回收状态');
            }

            if (!Schema::hasColumn('transfer_logs', 'external_status')) {
                $table->string('external_status', 50)->nullable()->after('recovery_status')->comment('上游订单状态');
            }

            if (!Schema::hasColumn('transfer_logs', 'external_checked_at')) {
                $table->timestamp('external_checked_at')->nullable()->after('external_status')->comment('上游状态检查时间');
            }

            if (!Schema::hasColumn('transfer_logs', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('external_checked_at')->comment('本地入账时间');
            }

            if (!Schema::hasColumn('transfer_logs', 'reconcile_note')) {
                $table->text('reconcile_note')->nullable()->after('posted_at')->comment('回收对账备注');
            }
        });

        $this->createIndexIfMissing('transfer_logs', 'transfer_logs_recovery_key_unique', 'CREATE UNIQUE INDEX `transfer_logs_recovery_key_unique` ON `transfer_logs` (`recovery_key`)');
        $this->createIndexIfMissing('transfer_logs', 'transfer_logs_recovery_active_idx', 'CREATE INDEX `transfer_logs_recovery_active_idx` ON `transfer_logs` (`user_id`, `api_type`, `transfer_type`, `recovery_status`)');
    }

    public function down()
    {
        $this->dropIndexIfExists('transfer_logs', 'transfer_logs_recovery_active_idx');
        $this->dropIndexIfExists('transfer_logs', 'transfer_logs_recovery_key_unique');

        Schema::table('transfer_logs', function (Blueprint $table) {
            if (Schema::hasColumn('transfer_logs', 'reconcile_note')) {
                $table->dropColumn('reconcile_note');
            }

            if (Schema::hasColumn('transfer_logs', 'posted_at')) {
                $table->dropColumn('posted_at');
            }

            if (Schema::hasColumn('transfer_logs', 'external_checked_at')) {
                $table->dropColumn('external_checked_at');
            }

            if (Schema::hasColumn('transfer_logs', 'external_status')) {
                $table->dropColumn('external_status');
            }

            if (Schema::hasColumn('transfer_logs', 'recovery_status')) {
                $table->dropColumn('recovery_status');
            }

            if (Schema::hasColumn('transfer_logs', 'recovery_key')) {
                $table->dropColumn('recovery_key');
            }
        });
    }

    protected function createIndexIfMissing($table, $indexName, $sql)
    {
        if (!$this->indexExists($table, $indexName)) {
            DB::statement($sql);
        }
    }

    protected function dropIndexIfExists($table, $indexName)
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement('DROP INDEX `'.$indexName.'` ON `'.$table.'`');
        }
    }

    protected function indexExists($table, $indexName)
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);
        return !empty($rows);
    }
}
