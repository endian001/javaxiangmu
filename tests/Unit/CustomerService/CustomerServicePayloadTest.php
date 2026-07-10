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

    private function controller()
    {
        return new class extends Controller {
            public function publicCustomerServicePayload()
            {
                return $this->customerServicePayload();
            }
        };
    }
}
