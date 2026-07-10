<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\PlatformSettingsController;
use App\Admin\Services\PlatformSettingsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformSettingsControllerBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('platform_app_builds');
        Schema::dropIfExists('platform_customer_services');
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('system_config');

        require_once database_path(
            'migrations/2026_07_10_000002_create_platform_settings_tables.php'
        );
        (new \CreatePlatformSettingsTables())->up();

        Schema::create('system_config', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->string('value', 5000)->default('');
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('admin_name', 120)->nullable();
            $table->string('action', 100);
            $table->string('module', 100);
            $table->text('content');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('context')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_customer_service_can_be_created_and_deleted()
    {
        $controller = $this->controller();
        $createRequest = Request::create('/game/tcg/platform-customer-services', 'POST', [
            'service_type' => 'custom',
            'display_name' => 'Controller behavior test',
            'service_url' => 'https://example.com/support',
            'position' => 12,
            'min_player_level' => 3,
            'status' => 1,
        ], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);
        $this->app->instance('request', $createRequest);

        $createResponse = $controller->saveCustomerService($createRequest);
        $createPayload = $createResponse->getData(true);

        $this->assertTrue($createPayload['status']);
        $row = DB::table('platform_customer_services')->first();
        $this->assertSame('Controller behavior test', $row->display_name);
        $this->assertSame(12, (int) $row->position);
        $this->assertSame(3, (int) $row->min_player_level);
        $this->assertSame(1, (int) $row->status);

        $deleteRequest = Request::create(
            '/game/tcg/platform-customer-services/'.$row->id,
            'DELETE',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit',
            ]
        );
        $this->app->instance('request', $deleteRequest);

        $deleteResponse = $controller->deleteCustomerService($row->id);
        $deletePayload = $deleteResponse->getData(true);

        $this->assertTrue($deletePayload['status']);
        $this->assertSame(0, DB::table('platform_customer_services')->count());
        $this->assertSame([
            'platform.customer_service.create',
            'platform.customer_service.delete',
        ], DB::table('admin_audit_logs')->orderBy('id')->pluck('action')->all());
    }

    public function test_app_build_request_creates_a_real_pending_queue_record()
    {
        foreach ([
            'platform_package_suffix' => 'com.example.verify',
            'platform_app_desktop_name' => 'Verification App',
            'platform_android_fixed_domain' => 'https://app.example.com',
        ] as $key => $value) {
            DB::table('system_config')->insert(compact('key', 'value'));
        }

        $request = Request::create('/game/tcg/platform-app-builds', 'POST', [
            'sync_download_links' => 1,
        ], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);
        $this->app->instance('request', $request);

        $response = $this->controller()->requestAppBuild($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertStringStartsWith('APP-', $payload['data']['build_no']);

        $row = DB::table('platform_app_builds')->first();
        $details = json_decode($row->details, true);

        $this->assertSame($payload['data']['build_no'], $row->build_no);
        $this->assertSame('pending', $row->status);
        $this->assertSame('com.example.verify', $row->package_name);
        $this->assertSame('https://app.example.com', $row->domain);
        $this->assertSame('Verification App', $details['desktop_name']);
        $this->assertTrue($details['sync_download_links']);
        $this->assertSame(
            7,
            Carbon::parse($row->requested_at)->diffInDays(Carbon::parse($row->expires_at))
        );
        $this->assertSame(
            'platform.app_build.request',
            DB::table('admin_audit_logs')->value('action')
        );
    }

    private function controller()
    {
        return new PlatformSettingsController(new PlatformSettingsService());
    }
}
