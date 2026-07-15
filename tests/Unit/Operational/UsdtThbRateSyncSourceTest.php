<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class UsdtThbRateSyncSourceTest extends TestCase
{
    public function test_usdt_thb_rate_sync_command_uses_coingecko_and_preserves_existing_rate_on_failure()
    {
        $root = dirname(__DIR__, 3);
        $servicePath = $root.'/app/Services/ExchangeRate/UsdtThbRateSyncService.php';
        $commandPath = $root.'/app/Console/Commands/SyncUsdtThbRate.php';
        $this->assertFileExists($servicePath);
        $this->assertFileExists($commandPath);

        $service = file_get_contents($servicePath);
        $command = file_get_contents($commandPath);

        $this->assertStringContainsString('https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=thb', $service);
        $this->assertStringContainsString("'tether'", $service);
        $this->assertStringContainsString("'thb'", $service);
        $this->assertStringContainsString('User-Agent', $service);
        $this->assertStringContainsString("SystemConfig::updateOrCreate(['key' => self::RATE_KEY]", $service);
        $this->assertStringContainsString('last good rate', $command);
        $this->assertStringNotContainsString('USDT-THB', $service);
        $this->assertStringNotContainsString('okx.com', strtolower($service));
    }

    public function test_usdt_thb_rate_sync_is_scheduled_daily_at_one_am()
    {
        $root = dirname(__DIR__, 3);
        $kernelPath = $root.'/app/Console/Kernel.php';
        $this->assertFileExists($kernelPath);

        $kernel = file_get_contents($kernelPath);

        $this->assertStringContainsString('Commands\\SyncUsdtThbRate::class', $kernel);
        $this->assertStringContainsString("command('exchange:sync-usdt-thb-rate')", $kernel);
        $this->assertStringContainsString("dailyAt('01:00')", $kernel);
        $this->assertStringContainsString('withoutOverlapping()', $kernel);
        $this->assertStringContainsString("storage_path('logs/usdt-thb-rate-sync.log')", $kernel);
    }
}
