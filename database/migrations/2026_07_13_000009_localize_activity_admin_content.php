<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalizeActivityAdminContent extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('activity_types', 'enname')) {
            Schema::table('activity_types', function (Blueprint $table) {
                $table->string('enname', 80)->nullable()->after('name');
            });
        }

        $now = date('Y-m-d H:i:s');
        $categories = [
            1 => ['name' => '新会员', 'enname' => 'สมาชิกใหม่', 'sort_order' => 900],
            2 => ['name' => '充值活动', 'enname' => 'ฝากเงิน', 'sort_order' => 880],
            3 => ['name' => '亏损返还', 'enname' => 'คืนยอดเสีย', 'sort_order' => 860],
            4 => ['name' => 'VIP', 'enname' => 'VIP', 'sort_order' => 840],
            5 => ['name' => '特别活动', 'enname' => 'กิจกรรมพิเศษ', 'sort_order' => 820],
            6 => ['name' => '邀请好友', 'enname' => 'ชวนเพื่อน', 'sort_order' => 800],
            7 => ['name' => '热门游戏', 'enname' => 'เกมยอดนิยม', 'sort_order' => 780],
            8 => ['name' => '公告', 'enname' => 'ประกาศ', 'sort_order' => 760],
        ];

        foreach ($categories as $id => $category) {
            $this->upsertCategory($id, $category, $now);
        }

        DB::table('activities')->where('id', 15)->update([
            'type' => 1,
            'title' => '新会员最高 1,888 奖金',
            'entitle' => 'ต้อนรับสมาชิกใหม่ โบนัสสูงสุด 1,888',
            'content' => '<p>注册成为新会员后，可按活动规则领取新人专属奖励。</p>',
            'encontent' => '<p>สมัครสมาชิกใหม่ รับสิทธิ์โบนัสต้อนรับและเริ่มเล่นเกมยอดนิยมได้ทันที</p>',
            'memo' => '<p>本活动仅限新会员参与，提交申请前请确认账号和活动规则。</p>',
            'enmemo' => '<p>สิทธิ์นี้สำหรับสมาชิกใหม่เท่านั้น กรุณาอ่านเงื่อนไขก่อนกดรับโปรโมชั่น</p>',
            'updated_at' => $now,
        ]);

        DB::table('activities')->where('id', 14)->update([
            'type' => 2,
            'title' => '首充特别奖金',
            'entitle' => 'ฝากครั้งแรก รับโบนัสพิเศษ',
            'content' => '<p>通过支持的充值渠道完成首充后，可按活动规则领取充值奖励。</p>',
            'encontent' => '<p>เติมเงินผ่านช่องทางที่รองรับ แล้วรับโบนัสฝากเงินตามเงื่อนไขของกิจกรรม</p>',
            'memo' => '<p>奖励金额和流水要求以活动规则为准，如需帮助请联系客服。</p>',
            'enmemo' => '<p>โบนัสขึ้นอยู่กับยอดฝากและเงื่อนไขเทิร์นโอเวอร์ หากต้องการความช่วยเหลือให้ติดต่อฝ่ายบริการลูกค้า</p>',
            'updated_at' => $now,
        ]);

        $referralType = (int) DB::table('activity_types')->where('id', 6)->value('id');
        if ($referralType > 0) {
            DB::table('activities')->where('banner', '/assets/promotions/referral-banner.webp')->update([
                'type' => $referralType,
                'title' => '邀请好友最高 28,888 奖金',
                'entitle' => 'ชวนเพื่อน รับโบนัสสูงสุด 28,888',
                'content' => '<p>分享邀请链接给好友，好友注册并完成活动要求后，可领取邀请奖励。</p>',
                'encontent' => '<p>แชร์ลิงก์ให้เพื่อน สมัครและเล่นตามเงื่อนไข รับโบนัสแนะนำเพื่อนได้จากกิจกรรมนี้</p>',
                'memo' => '<p>奖励会按活动条件和会员账号状态计算，如有疑问请联系客服。</p>',
                'enmemo' => '<p>โบนัสจะคำนวณตามเงื่อนไขกิจกรรมและสถานะบัญชีของสมาชิก หากมีข้อสงสัยให้ติดต่อฝ่ายบริการลูกค้า</p>',
                'action_url' => '',
                'requires_auth' => 0,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        // Keep localized admin content in place.
    }

    private function upsertCategory($id, array $category, $now)
    {
        $values = [
            'name' => $category['name'],
            'enname' => $category['enname'],
            'state' => 1,
            'sort_order' => $category['sort_order'],
            'updated_at' => $now,
        ];

        if (DB::table('activity_types')->where('id', $id)->exists()) {
            DB::table('activity_types')->where('id', $id)->update($values);
            return;
        }

        $values['id'] = $id;
        $values['created_at'] = $now;
        DB::table('activity_types')->insert($values);
    }
}
