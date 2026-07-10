<?php

namespace Tests\Unit\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemUserAlignmentMigrationTest extends TestCase
{
    public function test_it_creates_the_system_user_alignment_tables()
    {
        $migration = require database_path(
            'migrations/2026_07_10_000001_create_system_user_alignment_tables.php'
        );

        $migration->up();

        $this->assertTrue(Schema::hasColumns('admin_user_profiles', [
            'admin_user_id',
            'brand',
            'subscribed_brands',
            'google_auth_enabled',
            'status',
            'last_seen_at',
            'last_login_ip',
        ]));
        $this->assertTrue(Schema::hasColumns('admin_ip_whitelists', [
            'ip_address',
            'domain',
            'quota',
            'auto_cleanup_days',
            'is_important',
            'status',
        ]));
        $this->assertTrue(Schema::hasColumns('admin_tasks', [
            'task_no',
            'task_type',
            'title',
            'status',
            'payload',
            'result',
            'error_message',
        ]));
        $this->assertTrue(Schema::hasColumns('admin_audit_logs', [
            'admin_user_id',
            'admin_name',
            'action',
            'module',
            'content',
            'ip_address',
            'context',
        ]));
    }
}
