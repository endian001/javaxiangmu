<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueOrderIndexesForWalletTables extends Migration
{
    public function up()
    {
        $this->createUniqueIndex('recharge', 'order_no', 'recharge_order_no_unique', 100);
        $this->createUniqueIndex('recharge', 'out_trade_no', 'recharge_out_trade_no_unique', 100);
        $this->createUniqueIndex('withdraws', 'order_no', 'withdraws_order_no_unique');
        $this->createUniqueIndex('transfer_logs', 'order_no', 'transfer_logs_order_no_unique');
    }

    public function down()
    {
        $this->dropIndexIfExists('transfer_logs', 'transfer_logs_order_no_unique');
        $this->dropIndexIfExists('withdraws', 'withdraws_order_no_unique');
        $this->dropIndexIfExists('recharge', 'recharge_out_trade_no_unique');
        $this->dropIndexIfExists('recharge', 'recharge_order_no_unique');
    }

    protected function createUniqueIndex($table, $column, $indexName, $prefixLength = null)
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $this->assertNoDuplicates($table, $column, $prefixLength);

        $columnSql = '`'.$column.'`'.($prefixLength ? '('.(int) $prefixLength.')' : '');
        DB::statement('CREATE UNIQUE INDEX `'.$indexName.'` ON `'.$table.'` ('.$columnSql.')');
    }

    protected function assertNoDuplicates($table, $column, $prefixLength = null)
    {
        $expr = $prefixLength ? 'LEFT(`'.$column.'`, '.(int) $prefixLength.')' : '`'.$column.'`';
        $rows = DB::select(
            'SELECT '.$expr.' AS duplicate_key, COUNT(*) AS count_rows FROM `'.$table.'` '.
            'WHERE `'.$column.'` IS NOT NULL '.
            'GROUP BY '.$expr.' HAVING COUNT(*) > 1 LIMIT 1'
        );

        if (!empty($rows)) {
            throw new RuntimeException('Duplicate values block unique index '.$table.'.'.$column);
        }
    }

    protected function indexExists($table, $indexName)
    {
        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);
        return !empty($rows);
    }

    protected function dropIndexIfExists($table, $indexName)
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement('DROP INDEX `'.$indexName.'` ON `'.$table.'`');
        }
    }
}
