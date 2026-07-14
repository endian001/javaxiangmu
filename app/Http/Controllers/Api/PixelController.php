<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromotionPixelEventService;
use Illuminate\Http\Request;

class PixelController extends Controller
{
    public function record(Request $request)
    {
        $recorded = (new PromotionPixelEventService())->recordFromRequest($request);

        return $this->returnMsg(200, ['recorded' => $recorded], 'ok');
    }
}
