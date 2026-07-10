<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedThaiActivityCategories extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        if (DB::table('activity_types')->where('id', 14)->exists()) {
            DB::table('activity_types')->where('id', 14)->update([
                'name' => 'สมาชิกใหม่',
                'state' => 1,
                'sort_order' => 900,
                'updated_at' => $now,
            ]);
        }
        if (DB::table('activity_types')->where('id', 13)->exists()) {
            DB::table('activity_types')->where('id', 13)->update([
                'name' => 'ฝากเงิน',
                'state' => 1,
                'sort_order' => 860,
                'updated_at' => $now,
            ]);
        }

        $categories = [
            1 => 'สมาชิกใหม่',
            2 => 'สวัสดิการรายวัน',
            3 => 'สวัสดิการรายเดือน',
            4 => 'VIP',
            5 => 'ฝากเงิน',
            6 => 'ดาวน์โหลดแอป',
            7 => 'สล็อต',
            8 => 'อื่นๆ',
        ];

        $sort = 900;
        foreach ($categories as $position => $name) {
            $existing = DB::table('activity_types')->where('name', $name)->first();
            if ($existing) {
                DB::table('activity_types')->where('id', $existing->id)->update([
                    'state' => 1,
                    'sort_order' => $sort,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('activity_types')->insert([
                    'name' => $name,
                    'state' => 1,
                    'sort_order' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $sort -= 10;
        }

        $newMemberType = DB::table('activity_types')->where('name', 'สมาชิกใหม่')->value('id');
        $depositType = DB::table('activity_types')->where('name', 'ฝากเงิน')->value('id');

        if ($newMemberType) {
            DB::table('activities')->where('id', 15)->update([
                'type' => $newMemberType,
                'entitle' => 'ต้อนรับสมาชิกใหม่',
                'encontent' => '<p>รับโบนัสต้อนรับสำหรับสมาชิกใหม่ ตรวจสอบเงื่อนไขก่อนเข้าร่วมกิจกรรม</p>',
                'enmemo' => '<p>กิจกรรมเป็นไปตามข้อกำหนดของเว็บไซต์ สมาชิกหนึ่งคนเข้าร่วมได้หนึ่งครั้ง</p>',
                'sort_order' => 900,
                'is_popup' => 1,
                'popup_frequency' => 'daily',
                'popup_delay_seconds' => 1,
                'requires_auth' => 1,
                'updated_at' => $now,
            ]);
        }

        if ($depositType) {
            DB::table('activities')->where('id', 14)->update([
                'type' => $depositType,
                'entitle' => 'ฝากเงินรับโบนัสพิเศษ',
                'encontent' => '<p>เติมเครดิตและรับสิทธิพิเศษตามช่วงเวลาที่กำหนด</p>',
                'enmemo' => '<p>กรุณาอ่านเงื่อนไขยอดฝาก ยอดเทิร์น และเวลารับสิทธิ์ก่อนเข้าร่วม</p>',
                'sort_order' => 800,
                'requires_auth' => 1,
                'updated_at' => $now,
            ]);
        }

        if (!DB::table('activities')->where('is_popup', 1)->exists()) {
            DB::table('activities')->where('state', 1)->orderBy('sort_order', 'desc')->limit(1)->update([
                'is_popup' => 1,
                'popup_frequency' => 'daily',
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
