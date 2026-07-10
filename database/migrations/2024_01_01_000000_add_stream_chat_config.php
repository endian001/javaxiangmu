<?php

use App\Models\SystemConfig;
use Illuminate\Database\Migrations\Migration;

class AddStreamChatConfig extends Migration
{
    public function up()
    {
        $configs = [
            'stream_chat_api_key' => '',
            'stream_chat_secret' => '',
            'stream_chat_enabled' => '0',
            'stream_chat_message_limit' => '50',
        ];

        foreach ($configs as $key => $value) {
            SystemConfig::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down()
    {
        SystemConfig::whereIn('key', [
            'stream_chat_api_key',
            'stream_chat_secret',
            'stream_chat_enabled',
            'stream_chat_message_limit',
        ])->delete();
    }
}
