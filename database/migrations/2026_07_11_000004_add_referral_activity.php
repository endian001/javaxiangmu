<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddReferralActivity extends Migration
{
    private $title = 'ชวนเพื่อน รับโบนัสสูงสุด 28,888';

    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $type = DB::table('activity_types')->where('name', 'ชวนเพื่อน')->first();

        if ($type) {
            $typeId = (int) $type->id;
        } else {
            $typeId = (int) DB::table('activity_types')->insertGetId([
                'name' => 'ชวนเพื่อน',
                'state' => 1,
                'sort_order' => 800,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $values = [
            'type' => $typeId,
            'title' => $this->title,
            'entitle' => $this->title,
            'content' => '<p>แชร์ลิงก์ให้เพื่อน สมัครและเล่นตามเงื่อนไข รับโบนัสแนะนำเพื่อนได้จากกิจกรรมนี้</p>',
            'encontent' => '<p>แชร์ลิงก์ให้เพื่อน สมัครและเล่นตามเงื่อนไข รับโบนัสแนะนำเพื่อนได้จากกิจกรรมนี้</p>',
            'memo' => '<p>โบนัสจะคำนวณตามเงื่อนไขกิจกรรมและสถานะบัญชีของสมาชิก หากมีข้อสงสัยให้ติดต่อฝ่ายบริการลูกค้า</p>',
            'enmemo' => '<p>โบนัสจะคำนวณตามเงื่อนไขกิจกรรมและสถานะบัญชีของสมาชิก หากมีข้อสงสัยให้ติดต่อฝ่ายบริการลูกค้า</p>',
            'banner' => '/assets/promotions/referral-banner.webp',
            'app_img' => '/assets/promotions/referral-banner.webp',
            'can_apply' => 0,
            'state' => 1,
            'app_state' => 1,
            'sort_order' => 800,
            'starts_at' => null,
            'ends_at' => null,
            'is_popup' => 0,
            'popup_frequency' => 'daily',
            'popup_delay_seconds' => 0,
            'popup_image' => '/assets/promotions/referral-banner.webp',
            'app_popup_image' => '/assets/promotions/referral-banner.webp',
            'detail_image' => '/assets/promotions/referral-detail.jpg',
            'app_detail_image' => '/assets/promotions/referral-detail.jpg',
            'action_url' => '/member/center',
            'button_text' => 'ดูรายละเอียด',
            'requires_auth' => 1,
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
