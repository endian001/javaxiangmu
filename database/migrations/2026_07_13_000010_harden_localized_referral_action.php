<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class HardenLocalizedReferralAction extends Migration
{
    public function up()
    {
        DB::table('activities')
            ->where('banner', '/assets/promotions/referral-banner.webp')
            ->update([
                'action_url' => '',
                'requires_auth' => 0,
                'is_popup' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function down()
    {
        // Do not restore the unsafe referral jump.
    }
}
