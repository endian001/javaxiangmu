<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLiveChatTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('live_chat_sessions')) {
            Schema::create('live_chat_sessions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('session_no', 64)->unique();
                $table->string('visitor_id', 100)->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('username', 120)->nullable()->index();
                $table->string('status', 20)->default('open')->index();
                $table->unsignedInteger('assigned_admin_id')->nullable()->index();
                $table->text('last_message')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamp('last_user_message_at')->nullable();
                $table->timestamp('last_admin_message_at')->nullable();
                $table->unsignedInteger('admin_unread_count')->default(0);
                $table->unsignedInteger('user_unread_count')->default(0);
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('live_chat_messages')) {
            Schema::create('live_chat_messages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('session_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedInteger('admin_id')->nullable()->index();
                $table->string('sender_type', 20)->default('user')->index();
                $table->text('content');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('system_config')) {
            DB::table('system_config')->updateOrInsert(
                ['key' => 'internal_live_chat_enabled'],
                ['value' => '1']
            );
        }

        $this->upsertAdminMenu();
    }

    public function down()
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        if (Schema::hasTable($table)) {
            DB::table($table)->where('uri', 'live-chats')->delete();
        }

        if (Schema::hasTable('system_config')) {
            DB::table('system_config')->where('key', 'internal_live_chat_enabled')->delete();
        }

        Schema::dropIfExists('live_chat_messages');
        Schema::dropIfExists('live_chat_sessions');
    }

    private function upsertAdminMenu()
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        if (!Schema::hasTable($table)) {
            return;
        }

        $now = now();
        $values = [
            'parent_id' => 0,
            'order' => 18,
            'title' => '在线客服',
            'icon' => 'fa-comments',
            'uri' => 'live-chats',
            'updated_at' => $now,
        ];
        if (Schema::hasColumn($table, 'show')) {
            $values['show'] = 1;
        }
        if (Schema::hasColumn($table, 'extension')) {
            $values['extension'] = '';
        }

        $existing = DB::table($table)->where('uri', 'live-chats')->first();
        if ($existing) {
            DB::table($table)->where('id', $existing->id)->update($values);
            return;
        }

        $values['created_at'] = $now;
        DB::table($table)->insert($values);
    }
}
