<?php

namespace Tests\Unit\CustomerService;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerServicePayloadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('platform_customer_services');
        Schema::dropIfExists('system_config');

        Schema::create('platform_customer_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('service_type', 50);
            $table->string('display_name', 100);
            $table->string('service_url', 1000);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('min_player_level')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('system_config', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->string('value', 5000)->default('');
        });
    }

    public function test_payload_exposes_sorted_public_customer_service_entries()
    {
        DB::table('platform_customer_services')->insert([
            [
                'service_type' => 'line',
                'display_name' => 'LINE TH2W',
                'service_url' => 'https://line.me/R/ti/p/@th2w',
                'position' => 20,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'phone',
                'display_name' => 'Call center',
                'service_url' => 'tel:+6612345678',
                'position' => 10,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'telegram',
                'display_name' => 'VIP Telegram',
                'service_url' => 'https://t.me/th2w_vip',
                'position' => 5,
                'min_player_level' => 5,
                'status' => 1,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Disabled',
                'service_url' => 'https://support.example.org/disabled',
                'position' => 1,
                'min_player_level' => 0,
                'status' => 0,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Unsafe',
                'service_url' => 'javascript:alert(1)',
                'position' => 2,
                'min_player_level' => 0,
                'status' => 1,
            ],
        ]);

        $payload = $this->controller()->publicCustomerServicePayload();

        $this->assertSame(2, $payload['service_count']);
        $this->assertSame(
            ['phone', 'line'],
            array_column($payload['services'], 'service_type')
        );
        $this->assertSame(
            ['Call center', 'LINE TH2W'],
            array_column($payload['services'], 'display_name')
        );
        $this->assertSame(
            ['tel:+6612345678', 'https://line.me/R/ti/p/@th2w'],
            array_column($payload['services'], 'service_url')
        );
        $this->assertTrue($payload['configured']);
    }

    public function test_payload_includes_existing_platform_social_links()
    {
        DB::table('system_config')->insert([
            [
                'key' => 'platform_facebook_url',
                'value' => 'https://facebook.com/th2w',
            ],
            [
                'key' => 'platform_telegram_url',
                'value' => 'https://t.me/th2w',
            ],
            [
                'key' => 'platform_livechat_url',
                'value' => 'https://chat.example.co.th/th2w',
            ],
            [
                'key' => 'platform_whatsapp_url',
                'value' => 'javascript:alert(1)',
            ],
        ]);

        $payload = $this->controller()->publicCustomerServicePayload();

        $this->assertSame(3, $payload['service_count']);
        $this->assertSame(
            ['facebook', 'telegram', 'online'],
            array_column($payload['services'], 'service_type')
        );
    }

    public function test_payload_declares_realtime_mode_and_keeps_work_orders_as_fallback()
    {
        DB::table('system_config')->insert([
            [
                'key' => 'service_type',
                'value' => 'gongdan',
            ],
            [
                'key' => 'platform_livechat_url',
                'value' => 'https://chat.th2w-support.com/customer',
            ],
            [
                'key' => 'stream_chat_enabled',
                'value' => '1',
            ],
            [
                'key' => 'stream_chat_api_key',
                'value' => 'stream-ready-key',
            ],
            [
                'key' => 'stream_chat_secret',
                'value' => 'stream-ready-secret',
            ],
        ]);

        $payload = $this->controller()->publicCustomerServicePayload();

        $this->assertArrayHasKey('mode', $payload);
        $this->assertArrayHasKey('realtime_enabled', $payload);
        $this->assertArrayHasKey('fallback_url', $payload);
        $this->assertSame('realtime', $payload['mode']);
        $this->assertTrue($payload['realtime_enabled']);
        $this->assertStringEndsWith('/support/work-orders.html', $payload['fallback_url']);
        $this->assertSame('https://chat.th2w-support.com/customer', $payload['url']);
        $this->assertSame('https://chat.th2w-support.com/customer', $payload['service_url']);
        $this->assertStringEndsWith('/support/work-orders.html', $payload['work_order_page_url']);
    }

    public function test_work_order_page_is_not_reported_as_realtime_livechat()
    {
        DB::table('system_config')->insert([
            [
                'key' => 'service_type',
                'value' => 'gongdan',
            ],
            [
                'key' => 'platform_livechat_url',
                'value' => 'https://wakuang.fakaw.eu.cc/support/work-orders.html',
            ],
        ]);

        $payload = $this->controller()->publicCustomerServicePayload();

        $this->assertSame('work_order', $payload['mode']);
        $this->assertFalse($payload['realtime_enabled']);
        $this->assertSame('', $payload['realtime_url']);
        $this->assertStringEndsWith('/support/work-orders.html', $payload['url']);
        $this->assertStringEndsWith('/support/work-orders.html', $payload['fallback_url']);
    }

    public function test_internal_live_chat_is_primary_realtime_support_even_when_work_orders_are_enabled()
    {
        DB::table('system_config')->insert([
            [
                'key' => 'service_type',
                'value' => 'gongdan',
            ],
            [
                'key' => 'internal_live_chat_enabled',
                'value' => '1',
            ],
        ]);

        $payload = $this->controller()->publicCustomerServicePayload();

        $this->assertSame('realtime', $payload['mode']);
        $this->assertTrue($payload['realtime_enabled']);
        $this->assertSame('internal', $payload['realtime_provider']);
        $this->assertTrue($payload['internal_live_chat_enabled']);
        $this->assertStringEndsWith('/support/live-chat.html', $payload['url']);
        $this->assertStringEndsWith('/support/live-chat.html', $payload['livechat_url']);
        $this->assertStringEndsWith('/support/work-orders.html', $payload['fallback_url']);
        $this->assertStringEndsWith('/support/work-orders.html', $payload['work_order_page_url']);
    }

    public function test_payload_filters_placeholder_and_unsafe_third_party_customer_service_urls()
    {
        DB::table('platform_customer_services')->insert([
            [
                'service_type' => 'custom',
                'display_name' => 'Script',
                'service_url' => 'javascript:alert(1)',
                'position' => 1,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Example',
                'service_url' => 'https://example.com/support',
                'position' => 2,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Example Subdomain',
                'service_url' => 'https://support.example.org/live',
                'position' => 3,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Baidu',
                'service_url' => 'https://www.baidu.com/customer',
                'position' => 4,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Baidu Subdomain',
                'service_url' => 'https://chat.baidu.com/customer',
                'position' => 5,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'custom',
                'display_name' => 'Localhost',
                'service_url' => 'http://localhost/customer',
                'position' => 6,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'line',
                'display_name' => 'Real Line',
                'service_url' => 'https://line.me/R/ti/p/@th2w-real',
                'position' => 7,
                'min_player_level' => 0,
                'status' => 1,
            ],
        ]);

        DB::table('system_config')->insert([
            [
                'key' => 'kf_url',
                'value' => 'https://example.net/customer',
            ],
            [
                'key' => 'online_service_url',
                'value' => 'http://127.0.0.1/customer',
            ],
        ]);

        $payload = $this->controller()->publicCustomerServicePayload();
        $urls = array_column($payload['services'], 'service_url');

        $this->assertSame(['https://line.me/R/ti/p/@th2w-real'], $urls);
        $this->assertSame('', $payload['url']);
        $this->assertSame('', $payload['service_url']);
        foreach (['javascript:', 'example.', 'baidu.com', 'localhost', '127.0.0.1'] as $blocked) {
            $this->assertStringNotContainsString($blocked, implode(' ', $urls).' '.$payload['url']);
        }
    }

    public function test_payload_filters_entries_by_player_level()
    {
        DB::table('platform_customer_services')->insert([
            [
                'service_type' => 'line',
                'display_name' => 'Public Line',
                'service_url' => 'https://line.me/R/ti/p/@public',
                'position' => 10,
                'min_player_level' => 0,
                'status' => 1,
            ],
            [
                'service_type' => 'telegram',
                'display_name' => 'VIP Telegram',
                'service_url' => 'https://t.me/vip_support',
                'position' => 20,
                'min_player_level' => 3,
                'status' => 1,
            ],
        ]);

        $publicPayload = $this->controller()->publicCustomerServicePayload();
        $vipPayload = $this->controller()->levelCustomerServicePayload(3);

        $this->assertSame(['Public Line'], array_column($publicPayload['services'], 'display_name'));
        $this->assertSame(['Public Line', 'VIP Telegram'], array_column($vipPayload['services'], 'display_name'));
    }

    public function test_api_contact_endpoints_pass_authenticated_player_level()
    {
        $index = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/Api/IndexController.php');
        $app = file_get_contents(dirname(__DIR__, 3).'/app/Http/Controllers/Api/AppController.php');

        $this->assertStringContainsString('customerServicePayload($this->requestPlayerLevel($request))', $index);
        $this->assertStringContainsString('customerServicePayload($this->requestPlayerLevel($request))', $app);
        $this->assertStringContainsString('function requestPlayerLevel(', $index);
        $this->assertStringContainsString('function requestPlayerLevel(', $app);
    }

    private function controller()
    {
        return new class extends Controller {
            public function publicCustomerServicePayload()
            {
                return $this->customerServicePayload();
            }

            public function levelCustomerServicePayload($level)
            {
                return $this->customerServicePayload($level);
            }
        };
    }
}
