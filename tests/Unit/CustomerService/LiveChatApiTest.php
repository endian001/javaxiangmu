<?php

namespace Tests\Unit\CustomerService;

use App\Http\Controllers\Api\IndexController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveChatApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('live_chat_messages');
        Schema::dropIfExists('live_chat_sessions');
        Schema::dropIfExists('system_config');

        Schema::create('system_config', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->string('value', 5000)->default('');
        });

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

        DB::table('system_config')->insert([
            'key' => 'internal_live_chat_enabled',
            'value' => '1',
        ]);
    }

    public function test_guest_can_create_live_chat_session_and_send_message()
    {
        $controller = $this->controller();
        $sessionResponse = $controller->liveChatSession(Request::create('/api/live-chat/session', 'POST', [
            'visitor_id' => 'visitor-test-1234',
        ]));
        $sessionPayload = json_decode($sessionResponse->getContent(), true);

        $this->assertSame(200, $sessionPayload['code']);
        $this->assertSame('visitor-test-1234', $sessionPayload['data']['session']['visitor_id']);
        $this->assertSame('open', $sessionPayload['data']['session']['status']);

        $sessionId = $sessionPayload['data']['session']['id'];
        $sendResponse = $controller->liveChatSend(Request::create('/api/live-chat/messages', 'POST', [
            'visitor_id' => 'visitor-test-1234',
            'session_id' => $sessionId,
            'content' => 'Hello live support',
        ]));
        $sendPayload = json_decode($sendResponse->getContent(), true);

        $this->assertSame(200, $sendPayload['code']);
        $this->assertSame('user', $sendPayload['data']['message']['sender_type']);
        $this->assertSame('Hello live support', $sendPayload['data']['message']['content']);
        $this->assertSame(1, $sendPayload['data']['session']['admin_unread_count']);
        $this->assertSame(1, DB::table('live_chat_messages')->count());
    }

    private function controller()
    {
        return new class extends IndexController {
            public function __construct()
            {
            }
        };
    }
}
