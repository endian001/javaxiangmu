<?php

namespace Tests\Unit\Operational;

use App\Services\ExchangeRate\UsdtThbRateSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsdtThbRateSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('system_config');
        Schema::create('system_config', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
        });
    }

    public function test_sync_from_payload_persists_the_usdt_thb_rate_and_metadata()
    {
        $service = new UsdtThbRateSyncService();
        $result = $service->syncFromPayload([
            'tether' => [
                'thb' => 33.4812,
            ],
        ]);

        $this->assertSame('updated', $result['status']);
        $this->assertSame('33.4812', $result['rate']);
        $this->assertSame('33.4812', DB::table('system_config')->where('key', 'usdt_rate')->value('value'));
        $this->assertSame('coingecko', DB::table('system_config')->where('key', 'usdt_rate_source')->value('value'));
        $this->assertSame('success', DB::table('system_config')->where('key', 'usdt_rate_last_sync_status')->value('value'));
        $this->assertNotEmpty(DB::table('system_config')->where('key', 'usdt_rate_last_sync_at')->value('value'));
    }

    public function test_sync_from_payload_keeps_the_last_good_rate_when_the_payload_is_invalid()
    {
        DB::table('system_config')->insert([
            'key' => 'usdt_rate',
            'value' => '31.1100',
        ]);

        $service = new UsdtThbRateSyncService();
        $result = $service->syncFromPayload([
            'tether' => [
                'thb' => 0,
            ],
        ]);

        $this->assertSame('failed', $result['status']);
        $this->assertSame('31.1100', DB::table('system_config')->where('key', 'usdt_rate')->value('value'));
        $this->assertFalse(DB::table('system_config')->where('key', 'usdt_rate_source')->exists());
        $this->assertFalse(DB::table('system_config')->where('key', 'usdt_rate_last_sync_at')->exists());
    }
}
