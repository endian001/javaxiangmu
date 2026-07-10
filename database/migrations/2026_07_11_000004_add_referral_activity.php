<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddReferralActivity extends Migration
{
    private $title = 'แนะนำเพื่อน รับทรัพย์สูงสุด 28,888';

    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $type = DB::table('activity_types')->where('name', 'แนะนำเพื่อน')->first();

        if ($type) {
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) DB::table('activity_types')->insertGetId([
                'name' => 'แนะนำเพื่อน',
                'state' => 1,
                'sort_order' => 865,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $values = [
            'type' => $typeId,
            'title' => $this->title,
            'entitle' => $this->title,
            'content' => '<p>ชวนเพื่อนสมัครสมาชิกผ่านลิงก์แนะนำของคุณ รับรางวัลตามจำนวนเพื่อนที่ผ่านเงื่อนไขกิจกรรม</p>',
            'encontent' => '<p>ชวนเพื่อนสมัครสมาชิกผ่านลิงก์แนะนำของคุณ รับรางวัลตามจำนวนเพื่อนที่ผ่านเงื่อนไขกิจกรรม</p>',
            'memo' => '<p>เพื่อนที่แนะนำต้องเป็นสมาชิกใหม่และทำตามเงื่อนไขยอดฝากและยอดเทิร์น ระบบจะตรวจสอบสิทธิ์ก่อนจ่ายรางวัล</p>',
            'enmemo' => '<p>เพื่อนที่แนะนำต้องเป็นสมาชิกใหม่และทำตามเงื่อนไขยอดฝากและยอดเทิร์น ระบบจะตรวจสอบสิทธิ์ก่อนจ่ายรางวัล</p>',
            'banner' => '/assets/promotions/referral-banner.webp',
            'app_img' => '/assets/promotions/referral-banner.webp',
            'can_apply' => 0,
            'state' => 1,
            'sort_order' => 850,
            'starts_at' => null,
            'ends_at' => null,
            'is_popup' => 0,
            'popup_frequency' => 'daily',
            'popup_delay_seconds' => 0,
            'popup_image' => '/assets/promotions/referral-banner.webp',
            'app_popup_image' => '/assets/promotions/referral-banner.webp',
            'detail_image' => '/assets/promotions/referral-detail.jpg',
            'app_detail_image' => '/assets/promotions/referral-detail.jpg',
            'action_url' => '',
            'requires_auth' => 0,
            'updated_at' => $now,
        ];

        $activity = DB::table('activities')
            ->where('banner', '/assets/promotions/referral-banner.webp')
            ->first();

        if ($activity) {
            DB::table('activities')->where('id', $activity->id)->update($values);
            return;
        }

        $values['created_at'] = $now;
        DB::table('activities')->insert($values);
    }

    public function down()
    {
        DB::table('activities')
            ->where('banner', '/assets/promotions/referral-banner.webp')
            ->delete();
    }
}
