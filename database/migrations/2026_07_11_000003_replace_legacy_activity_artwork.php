<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReplaceLegacyActivityArtwork extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        DB::table('activities')->where('id', 15)->update([
            'title' => 'ต้อนรับสมาชิกใหม่',
            'entitle' => 'ต้อนรับสมาชิกใหม่',
            'content' => '<p>สมัครสมาชิก ฝากเงินครั้งแรก และรับโบนัสต้อนรับตามเงื่อนไขของกิจกรรม</p>',
            'encontent' => '<p>สมัครสมาชิก ฝากเงินครั้งแรก และรับโบนัสต้อนรับตามเงื่อนไขของกิจกรรม</p>',
            'memo' => '<p>สมาชิกหนึ่งคนรับสิทธิ์ได้หนึ่งครั้ง กรุณาตรวจสอบยอดฝากและยอดเทิร์นก่อนเข้าร่วม</p>',
            'enmemo' => '<p>สมาชิกหนึ่งคนรับสิทธิ์ได้หนึ่งครั้ง กรุณาตรวจสอบยอดฝากและยอดเทิร์นก่อนเข้าร่วม</p>',
            'banner' => '/assets/promotions/welcome-banner.png',
            'app_img' => '/assets/promotions/welcome-banner.png',
            'popup_image' => '/assets/promotions/welcome-banner.png',
            'app_popup_image' => '/assets/promotions/welcome-banner.png',
            'detail_image' => '/assets/promotions/welcome-detail.png',
            'app_detail_image' => '/assets/promotions/welcome-detail.png',
            'updated_at' => $now,
        ]);

        DB::table('activities')->where('id', 14)->update([
            'title' => 'ฝากเงินรับโบนัสพิเศษ',
            'entitle' => 'ฝากเงินรับโบนัสพิเศษ',
            'content' => '<p>เติมเครดิตตามยอดที่กำหนดและรับโบนัสเพิ่มสำหรับการเล่นเกมที่ร่วมรายการ</p>',
            'encontent' => '<p>เติมเครดิตตามยอดที่กำหนดและรับโบนัสเพิ่มสำหรับการเล่นเกมที่ร่วมรายการ</p>',
            'memo' => '<p>โบนัสและยอดเทิร์นขึ้นอยู่กับช่วงเวลาและประเภทเกม กรุณาอ่านรายละเอียดก่อนรับสิทธิ์</p>',
            'enmemo' => '<p>โบนัสและยอดเทิร์นขึ้นอยู่กับช่วงเวลาและประเภทเกม กรุณาอ่านรายละเอียดก่อนรับสิทธิ์</p>',
            'banner' => '/assets/promotions/deposit-banner.png',
            'app_img' => '/assets/promotions/deposit-banner.png',
            'popup_image' => '/assets/promotions/deposit-banner.png',
            'app_popup_image' => '/assets/promotions/deposit-banner.png',
            'detail_image' => '/assets/promotions/deposit-detail.png',
            'app_detail_image' => '/assets/promotions/deposit-detail.png',
            'updated_at' => $now,
        ]);
    }

    public function down()
    {
        // Keep the Thai artwork in place instead of restoring the legacy Chinese images.
    }
}
