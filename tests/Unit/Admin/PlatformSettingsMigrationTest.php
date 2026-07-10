<?php

namespace Tests\Unit\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformSettingsMigrationTest extends TestCase
{
    public function test_it_creates_customer_service_and_app_build_tables()
    {
        require_once database_path(
            'migrations/2026_07_10_000002_create_platform_settings_tables.php'
        );
        (new \CreatePlatformSettingsTables())->up();

        $this->assertTrue(Schema::hasColumns('platform_customer_services', [
            'service_type',
            'display_name',
            'service_url',
            'position',
            'min_player_level',
            'status',
        ]));
        $this->assertTrue(Schema::hasColumns('platform_app_builds', [
            'build_no',
            'package_name',
            'domain',
            'status',
            'ios_url',
            'android_url',
            'requested_by',
            'expires_at',
        ]));
    }
}
