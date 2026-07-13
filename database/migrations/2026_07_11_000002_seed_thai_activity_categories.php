<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedThaiActivityCategories extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $categories = [
            1 => ['name' => 'สมาชิกใหม่', 'sort_order' => 900],
            2 => ['name' => 'ฝากเงิน', 'sort_order' => 880],
            3 => ['name' => 'คืนยอดเสีย', 'sort_order' => 860],
            4 => ['name' => 'VIP', 'sort_order' => 840],
            5 => ['name' => 'กิจกรรมพิเศษ', 'sort_order' => 820],
            6 => ['name' => 'ชวนเพื่อน', 'sort_order' => 800],
            7 => ['name' => 'เกมยอดนิยม', 'sort_order' => 780],
            8 => ['name' => 'ประกาศ', 'sort_order' => 760],
        ];

        foreach ($categories as $id => $category) {
            if (DB::table('activity_types')->where('id', $id)->exists()) {
                DB::table('activity_types')->where('id', $id)->update([
                    'name' => $category['name'],
                    'state' => 1,
                    'sort_order' => $category['sort_order'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('activity_types')->insert([
                    'id' => $id,
                    'name' => $category['name'],
                    'state' => 1,
                    'sort_order' => $category['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('activities')->where('id', 15)->update([
            'type' => 1,
            'entitle' => 'ต้อนรับสมาชิกใหม่ โบนัสสูงสุด 1,888',
            'encontent' => '<p>สมัครสมาชิกใหม่ รับสิทธิพิเศษสำหรับผู้เล่นใหม่ เลือกเกมที่ชอบและเริ่มเล่นได้ทันที</p>',
            'enmemo' => '<p>โปรโมชั่นนี้สำหรับสมาชิกใหม่เท่านั้น กรุณาตรวจสอบเงื่อนไขก่อนรับสิทธิ์</p>',
            'sort_order' => 900,
            'is_popup' => 1,
            'popup_frequency' => 'once',
            'popup_delay_seconds' => 1,
            'requires_auth' => 1,
            'can_apply' => 1,
            'updated_at' => $now,
        ]);

        DB::table('activities')->where('id', 14)->update([
            'type' => 2,
            'entitle' => 'ฝากครั้งแรก รับโบนัสพิเศษ',
            'encontent' => '<p>เติมเงินผ่านช่องทางที่รองรับ แล้วรับโบนัสฝากเงินตามเงื่อนไขของกิจกรรม</p>',
            'enmemo' => '<p>โบนัสขึ้นอยู่กับยอดฝากและเงื่อนไขเทิร์นโอเวอร์ กรุณาติดต่อฝ่ายบริการลูกค้าหากต้องการความช่วยเหลือ</p>',
            'sort_order' => 850,
            'requires_auth' => 1,
            'can_apply' => 0,
            'action_url' => '/member/recharge',
            'updated_at' => $now,
        ]);

        if (!DB::table('activities')->where('is_popup', 1)->exists()) {
            DB::table('activities')->where('state', 1)->orderBy('sort_order', 'desc')->limit(1)->update([
                'is_popup' => 1,
                'popup_frequency' => 'once',
                'popup_delay_seconds' => 1,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        DB::table('activities')->whereIn('id', [14, 15])->update([
            'is_popup' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
