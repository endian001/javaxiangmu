<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanActivityPromotionsFinal extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('activities', 'button_text')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->string('button_text', 80)->nullable()->after('action_url');
            });
        }

        $now = date('Y-m-d H:i:s');
        $this->upsertCategory(1, 'สมาชิกใหม่', 900, $now);
        $this->upsertCategory(2, 'ฝากเงิน', 880, $now);
        $this->upsertCategory(3, 'คืนยอดเสีย', 860, $now);
        $this->upsertCategory(4, 'VIP', 840, $now);
        $this->upsertCategory(5, 'กิจกรรมพิเศษ', 820, $now);
        $this->upsertCategory(6, 'ชวนเพื่อน', 800, $now);

        DB::table('activities')->where('id', 15)->update([
            'type' => 1,
            'title' => 'ต้อนรับสมาชิกใหม่ โบนัสสูงสุด 1,888',
            'entitle' => 'ต้อนรับสมาชิกใหม่ โบนัสสูงสุด 1,888',
            'content' => '<p>สมัครสมาชิกใหม่ รับสิทธิ์โบนัสต้อนรับและเริ่มเล่นเกมยอดนิยมได้ทันที</p>',
            'encontent' => '<p>สมัครสมาชิกใหม่ รับสิทธิ์โบนัสต้อนรับและเริ่มเล่นเกมยอดนิยมได้ทันที</p>',
            'memo' => '<p>สิทธิ์นี้สำหรับสมาชิกใหม่เท่านั้น กรุณาอ่านเงื่อนไขก่อนกดรับโปรโมชั่น</p>',
            'enmemo' => '<p>สิทธิ์นี้สำหรับสมาชิกใหม่เท่านั้น กรุณาอ่านเงื่อนไขก่อนกดรับโปรโมชั่น</p>',
            'banner' => '/assets/promotions/welcome-banner.png',
            'app_img' => '/assets/promotions/welcome-banner.png',
            'popup_image' => '/assets/promotions/welcome-banner.png',
            'app_popup_image' => '/assets/promotions/welcome-banner.png',
            'detail_image' => '/assets/promotions/welcome-detail.png',
            'app_detail_image' => '/assets/promotions/welcome-detail.png',
            'sort_order' => 900,
            'is_popup' => 1,
            'popup_frequency' => 'once',
            'popup_delay_seconds' => 1,
            'requires_auth' => 1,
            'can_apply' => 1,
            'button_text' => 'รับโปรโมชั่น',
            'state' => 1,
            'app_state' => 1,
            'updated_at' => $now,
        ]);

        DB::table('activities')->where('id', 14)->update([
            'type' => 2,
            'title' => 'ฝากครั้งแรก รับโบนัสพิเศษ',
            'entitle' => 'ฝากครั้งแรก รับโบนัสพิเศษ',
            'content' => '<p>เติมเงินผ่านช่องทางที่รองรับ แล้วรับโบนัสฝากเงินตามเงื่อนไขของกิจกรรม</p>',
            'encontent' => '<p>เติมเงินผ่านช่องทางที่รองรับ แล้วรับโบนัสฝากเงินตามเงื่อนไขของกิจกรรม</p>',
            'memo' => '<p>โบนัสขึ้นอยู่กับยอดฝากและเงื่อนไขเทิร์นโอเวอร์ หากต้องการความช่วยเหลือให้ติดต่อฝ่ายบริการลูกค้า</p>',
            'enmemo' => '<p>โบนัสขึ้นอยู่กับยอดฝากและเงื่อนไขเทิร์นโอเวอร์ หากต้องการความช่วยเหลือให้ติดต่อฝ่ายบริการลูกค้า</p>',
            'banner' => '/assets/promotions/deposit-banner.png',
            'app_img' => '/assets/promotions/deposit-banner.png',
            'popup_image' => '/assets/promotions/deposit-banner.png',
            'app_popup_image' => '/assets/promotions/deposit-banner.png',
            'detail_image' => '/assets/promotions/deposit-detail.png',
            'app_detail_image' => '/assets/promotions/deposit-detail.png',
            'sort_order' => 850,
            'requires_auth' => 1,
            'can_apply' => 0,
            'action_url' => '/member/recharge',
            'button_text' => 'เติมเงินทันที',
            'state' => 1,
            'app_state' => 1,
            'updated_at' => $now,
        ]);

        $referralType = (int) DB::table('activity_types')->where('name', 'ชวนเพื่อน')->value('id');
        if ($referralType > 0) {
            $values = [
                'type' => $referralType,
                'title' => 'ชวนเพื่อน รับโบนัสสูงสุด 28,888',
                'entitle' => 'ชวนเพื่อน รับโบนัสสูงสุด 28,888',
                'content' => '<p>แชร์ลิงก์ให้เพื่อน สมัครและเล่นตามเงื่อนไข รับโบนัสแนะนำเพื่อนได้จากกิจกรรมนี้</p>',
                'encontent' => '<p>แชร์ลิงก์ให้เพื่อน สมัครและเล่นตามเงื่อนไข รับโบนัสแนะนำเพื่อนได้จากกิจกรรมนี้</p>',
                'memo' => '<p>โบนัสจะคำนวณตามเงื่อนไขกิจกรรมและสถานะบัญชีของสมาชิก หากมีข้อสงสัยให้ติดต่อฝ่ายบริการลูกค้า</p>',
                'enmemo' => '<p>โบนัสจะคำนวณตามเงื่อนไขกิจกรรมและสถานะบัญชีของสมาชิก หากมีข้อสงสัยให้ติดต่อฝ่ายบริการลูกค้า</p>',
                'banner' => '/assets/promotions/referral-banner.webp',
                'app_img' => '/assets/promotions/referral-banner.webp',
                'popup_image' => '/assets/promotions/referral-banner.webp',
                'app_popup_image' => '/assets/promotions/referral-banner.webp',
                'detail_image' => '/assets/promotions/referral-detail.jpg',
                'app_detail_image' => '/assets/promotions/referral-detail.jpg',
                'can_apply' => 0,
                'state' => 1,
                'app_state' => 1,
                'sort_order' => 800,
                'is_popup' => 0,
                'popup_frequency' => 'once',
                'popup_delay_seconds' => 0,
                'action_url' => '/member/center',
                'button_text' => 'ดูรายละเอียด',
                'requires_auth' => 1,
                'updated_at' => $now,
            ];

            $activity = DB::table('activities')->where('banner', '/assets/promotions/referral-banner.webp')->first();
            if ($activity) {
                DB::table('activities')->where('id', $activity->id)->update($values);
            } else {
                $values['created_at'] = $now;
                DB::table('activities')->insert($values);
            }
        }
    }

    public function down()
    {
        // Keep cleaned promotion data in place.
    }

    private function upsertCategory($id, $name, $sortOrder, $now)
    {
        if (DB::table('activity_types')->where('id', $id)->exists()) {
            DB::table('activity_types')->where('id', $id)->update([
                'name' => $name,
                'state' => 1,
                'sort_order' => $sortOrder,
                'updated_at' => $now,
            ]);
            return;
        }

        DB::table('activity_types')->insert([
            'id' => $id,
            'name' => $name,
            'state' => 1,
            'sort_order' => $sortOrder,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
