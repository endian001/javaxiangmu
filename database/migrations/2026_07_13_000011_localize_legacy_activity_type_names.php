<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalizeLegacyActivityTypeNames extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('activity_types', 'enname')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $types = [
            13 => ['name' => '充值活动', 'enname' => 'ฝากเงิน'],
            14 => ['name' => '新会员', 'enname' => 'สมาชิกใหม่'],
            15 => ['name' => '每日福利', 'enname' => 'สวัสดิการรายวัน'],
            16 => ['name' => '每月福利', 'enname' => 'สวัสดิการรายเดือน'],
            17 => ['name' => 'VIP', 'enname' => 'VIP'],
            18 => ['name' => '下载APP', 'enname' => 'ดาวน์โหลดแอป'],
            19 => ['name' => '电子老虎机', 'enname' => 'สล็อต'],
            20 => ['name' => '其他', 'enname' => 'อื่นๆ'],
            21 => ['name' => '邀请好友', 'enname' => 'แนะนำเพื่อน'],
        ];

        foreach ($types as $id => $type) {
            DB::table('activity_types')->where('id', $id)->update([
                'name' => $type['name'],
                'enname' => $type['enname'],
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        // Keep admin names localized.
    }
}
