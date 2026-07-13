<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReplaceLegacyActivityArtwork extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        DB::table('activities')->where('id', 15)->update([
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
            'button_text' => 'รับโปรโมชั่น',
            'updated_at' => $now,
        ]);

        DB::table('activities')->where('id', 14)->update([
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
            'button_text' => 'เติมเงินทันที',
            'action_url' => '/member/recharge',
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        // Keep the cleaned artwork and Thai promotion copy in place.
    }
}
