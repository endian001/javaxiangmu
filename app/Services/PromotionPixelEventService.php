<?php

namespace App\Services;

use App\Services\Tracking\MultiPlatformTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromotionPixelEventService
{
    public function recordFromRequest(Request $request)
    {
        try {
            // Legacy promotion_event_records writes are handled inside MultiPlatformTrackingService.
            return (new MultiPlatformTrackingService())->recordBrowserEvent($request);
        } catch (\Throwable $e) {
            Log::warning('promotion pixel event record failed', ['message' => $e->getMessage()]);

            return false;
        }
    }

    public function recordDepositArrival($recharge, array $context = [])
    {
        try {
            // Legacy promotion_event_records writes are handled inside MultiPlatformTrackingService.
            return (new MultiPlatformTrackingService())->recordRechargeArrival($recharge, $context);
        } catch (\Throwable $e) {
            Log::warning('promotion deposit pixel event record failed', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
