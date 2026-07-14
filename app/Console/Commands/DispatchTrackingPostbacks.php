<?php

namespace App\Console\Commands;

use App\Services\Tracking\TrackingPostbackDispatcher;
use Illuminate\Console\Command;

class DispatchTrackingPostbacks extends Command
{
    protected $signature = 'tracking:dispatch-postbacks {--limit=50}';

    protected $description = 'Dispatch pending advertising postback logs.';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $summary = (new TrackingPostbackDispatcher())->dispatchPending($limit);

        $this->info(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
