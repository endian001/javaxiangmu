<?php

namespace Tests\Unit\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionChannelMigrationTest extends TestCase
{
    public function test_it_creates_promotion_storage_tables()
    {
        $path = database_path(
            'migrations/2026_07_10_000003_create_promotion_channel_tables.php'
        );
        $this->assertFileExists($path);

        require_once $path;
        (new \CreatePromotionChannelTables())->up();

        $this->assertTrue(Schema::hasColumns('promotion_channel_items', [
            'module',
            'item_type',
            'name',
            'domain',
            'owner',
            'target',
            'status',
            'position',
            'data',
        ]));
        $this->assertTrue(Schema::hasColumns('promotion_channel_settings', [
            'module',
            'setting_key',
            'setting_value',
            'updated_by',
        ]));
        $this->assertTrue(Schema::hasColumns('promotion_push_jobs', [
            'template_id',
            'title',
            'content',
            'audience_type',
            'scheduled_at',
            'status',
            'total_count',
            'success_count',
        ]));
        $this->assertTrue(Schema::hasColumns('promotion_event_records', [
            'link_id',
            'facebook_pixel_id',
            'tiktok_pixel_id',
            'agent_account',
            'username',
            'event',
            'event_at',
            'amount',
            'raw_record',
        ]));
    }
}
