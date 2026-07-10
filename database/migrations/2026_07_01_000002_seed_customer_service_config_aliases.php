<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedCustomerServiceConfigAliases extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('system_config')) {
            return;
        }

        $url = DB::table('system_config')->where('key', 'kf_url')->value('value');
        $url = $url ?: 'https://wakuang.fakaw.eu.cc/support/work-orders.html';

        $values = [
            'kf_url' => $url,
            'service_type' => 'gongdan',
            'work_order_enabled' => '1',
            'service_url' => $url,
            'service_link' => $url,
            'customer_service_url' => $url,
            'online_service_url' => $url,
            'stream_chat_enabled' => DB::table('system_config')->where('key', 'stream_chat_enabled')->value('value') ?: '0',
            'stream_chat_api_key' => DB::table('system_config')->where('key', 'stream_chat_api_key')->value('value') ?: '',
            'stream_chat_secret' => DB::table('system_config')->where('key', 'stream_chat_secret')->value('value') ?: '',
            'stream_chat_message_limit' => DB::table('system_config')->where('key', 'stream_chat_message_limit')->value('value') ?: '50',
        ];

        foreach ($values as $key => $value) {
            DB::table('system_config')->updateOrInsert(['key' => $key], ['value' => $value]);
        }
    }

    public function down()
    {
        if (!Schema::hasTable('system_config')) {
            return;
        }

        DB::table('system_config')->whereIn('key', [
            'service_url',
            'service_link',
            'customer_service_url',
            'online_service_url',
        ])->delete();
    }
}
