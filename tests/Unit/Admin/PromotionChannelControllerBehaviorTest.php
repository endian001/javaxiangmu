<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\PromotionChannelController;
use App\Admin\Services\PromotionChannelService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionChannelControllerBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('promotion_event_records');
        Schema::dropIfExists('promotion_push_jobs');
        Schema::dropIfExists('promotion_channel_settings');
        Schema::dropIfExists('promotion_channel_items');
        Schema::dropIfExists('admin_audit_logs');

        require_once database_path(
            'migrations/2026_07_10_000003_create_promotion_channel_tables.php'
        );
        (new \CreatePromotionChannelTables())->up();

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

    public function test_promotion_link_can_be_created_and_deleted()
    {
        $request = $this->request('/game/tcg/promotion/280000/items', 'POST', [
            'item_type' => 'main',
            'domain' => 'promo.example.com',
            'owner' => 'agent001',
            'target' => 'https://promo.example.com/m/index.html',
            'status' => 1,
            'https_status' => 'enabled',
            'cloak_enabled' => 1,
            'ad_review_passed' => 1,
            'tool' => 'Facebook',
            'application' => 'H5',
            'execution_status' => 'ready',
            'note' => 'behavior test',
        ]);

        $response = $this->controller()->saveItem($request, '280000');
        $payload = $response->getData(true);
        $row = DB::table('promotion_channel_items')->first();
        $meta = json_decode($row->data, true);

        $this->assertTrue($payload['status']);
        $this->assertSame('links', $row->module);
        $this->assertSame('promo.example.com', $row->domain);
        $this->assertSame('Facebook', $meta['tool']);
        $this->assertSame('1', $meta['cloak_enabled']);

        $deleteRequest = $this->request(
            '/game/tcg/promotion/280000/items/'.$row->id,
            'DELETE'
        );
        $deleteResponse = $this->controller()->deleteItem('280000', $row->id);

        $this->assertTrue($deleteResponse->getData(true)['status']);
        $this->assertSame(0, DB::table('promotion_channel_items')->count());
    }

    public function test_domain_batch_setting_updates_matching_targets()
    {
        foreach (['a.example.com', 'b.example.com'] as $domain) {
            DB::table('promotion_channel_items')->insert([
                'module' => 'domains',
                'item_type' => 'promotion',
                'domain' => $domain,
                'target' => 'old-target.example.com',
                'status' => 1,
                'position' => 0,
                'data' => '{}',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $request = $this->request('/game/tcg/promotion/21160/settings', 'POST', [
            'settings' => [
                'operation' => 'replace_target',
                'old_target' => 'old-target.example.com',
                'new_target' => 'new-target.example.com',
            ],
        ]);

        $response = $this->controller()->saveSettings($request, '21160');
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertSame(2, $payload['data']['updated_items']);
        $this->assertSame(
            2,
            DB::table('promotion_channel_items')
                ->where('target', 'new-target.example.com')
                ->count()
        );
        $this->assertSame(
            'new-target.example.com',
            DB::table('promotion_channel_settings')
                ->where('module', 'domains')
                ->where('setting_key', 'new_target')
                ->value('setting_value')
        );
    }

    public function test_push_template_and_job_are_persisted_in_real_queue()
    {
        $templateRequest = $this->request('/game/tcg/promotion/280015/items', 'POST', [
            'item_type' => 'template',
            'name' => 'Welcome template',
            'status' => 1,
            'push_title' => 'Welcome',
            'push_content' => 'Install completed, return to register.',
        ]);
        $templateResponse = $this->controller()->saveItem(
            $templateRequest,
            '280015'
        );
        $templateId = $templateResponse->getData(true)['data']['id'];

        $jobRequest = $this->request('/game/tcg/promotion/280015/push-jobs', 'POST', [
            'template_id' => $templateId,
            'audience_type' => 'never',
            'send_mode' => 'immediate',
        ]);
        $jobResponse = $this->controller()->createPushJob($jobRequest, '280015');
        $job = DB::table('promotion_push_jobs')->first();

        $this->assertTrue($jobResponse->getData(true)['status']);
        $this->assertSame($templateId, (int) $job->template_id);
        $this->assertSame('Welcome', $job->title);
        $this->assertSame('never', $job->audience_type);
        $this->assertSame('queued', $job->status);
    }

    public function test_event_export_contains_the_real_event_record()
    {
        DB::table('promotion_event_records')->insert([
            'link_id' => 88,
            'facebook_pixel_id' => 'fb-verify',
            'tiktok_pixel_id' => 'tt-verify',
            'from_facebook' => 1,
            'agent_account' => 'agent001',
            'user_id' => 1001,
            'username' => 'verify-user',
            'event' => 'register',
            'event_at' => now(),
            'amount' => 0,
            'raw_record' => '{"source":"test"}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $request = $this->request(
            '/game/tcg/promotion/280012/events/export',
            'GET',
            ['event' => 'register']
        );

        $response = $this->controller()->exportEvents($request, '280012');
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('promotion-events-', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('fb-verify', $csv);
        $this->assertStringContainsString('verify-user', $csv);
        $this->assertStringContainsString('register', $csv);
    }

    private function request($uri, $method, array $data = [])
    {
        $request = Request::create($uri, $method, $data, [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);
        $this->app->instance('request', $request);

        return $request;
    }

    private function controller()
    {
        return new PromotionChannelController(new PromotionChannelService());
    }
}
