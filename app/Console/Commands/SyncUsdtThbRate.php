<?php

namespace App\Console\Commands;

use App\Services\ExchangeRate\UsdtThbRateSyncService;
use Illuminate\Console\Command;

class SyncUsdtThbRate extends Command
{
    protected $signature = 'exchange:sync-usdt-thb-rate';

    protected $description = 'Sync the USDT/THB deposit rate from CoinGecko and keep the last good rate on failure.';

    public function handle()
    {
        $result = (new UsdtThbRateSyncService())->sync();
        $this->info(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ($result['status'] ?? '') === 'updated' ? 0 : 1;
    }
}
