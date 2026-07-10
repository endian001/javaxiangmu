<?php

namespace Tests\Unit\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperationsMigrationTest extends TestCase
{
    public function test_it_creates_first_wave_storage_tables()
    {
        $path = database_path(
            'migrations/2026_07_10_000005_create_platform_operations_tables.php'
        );
        $this->assertFileExists($path);

        require_once $path;
        (new \CreatePlatformOperationsTables())->up();

        $this->assertTrue(Schema::hasColumns('admin_module_records', [
            'page_code',
            'record_type',
            'title',
            'status',
            'sort_order',
            'amount',
            'currency',
            'effective_at',
            'business_data',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('admin_module_settings', [
            'page_code',
            'section',
            'setting_key',
            'setting_value',
            'updated_by',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('admin_module_transactions', [
            'page_code',
            'business_no',
            'transaction_type',
            'account_name',
            'account_no',
            'amount',
            'balance_before',
            'balance_after',
            'currency',
            'status',
            'occurred_at',
            'remark',
            'business_data',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ]));
    }
}
