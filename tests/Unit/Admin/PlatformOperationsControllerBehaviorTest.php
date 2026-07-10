<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\PlatformOperationsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperationsControllerBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.permission.enable' => false]);

        foreach ([
            'admin_module_transactions',
            'admin_module_settings',
            'admin_module_records',
            'admin_audit_logs',
            'system_config',
            'pay_types',
            'recharge',
            'withdraws',
            'transfer_logs',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        require_once database_path(
            'migrations/2026_07_10_000005_create_platform_operations_tables.php'
        );
        (new \CreatePlatformOperationsTables())->up();

        Schema::create('system_config', function (Blueprint $table) {
            $table->string('key', 50)->primary();
            $table->text('value');
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

        Schema::create('pay_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->decimal('bonus_ratio', 10, 2)->nullable();
            $table->integer('state')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_settings_are_persisted_to_system_config_and_module_settings()
    {
        $request = $this->request('POST', [
            'section' => 'general',
            'site_name' => 'WAKUANG',
            'site_title' => 'WAKUANG Platform',
            'not_allowed' => 'must-not-pass',
        ]);

        $response = $this->controller()->saveSettings($request, '90510');
        $payload = $response->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertSame('WAKUANG', DB::table('system_config')
            ->where('key', 'site_name')
            ->value('value'));
        $this->assertSame(2, DB::table('admin_module_settings')->count());
        $this->assertFalse(DB::table('system_config')
            ->where('key', 'not_allowed')
            ->exists());
        $this->assertSame(
            'platform_operations.settings.update',
            DB::table('admin_audit_logs')->value('action')
        );
    }

    public function test_record_can_be_created_status_changed_and_deleted()
    {
        $create = $this->request('POST', [
            'title' => '主线路',
            'status' => 'enabled',
            'sort_order' => 10,
            'domain' => 'https://line.example.com',
            'line_type' => 'primary',
        ]);
        $createPayload = $this->controller()
            ->saveRecord($create, '36000')
            ->getData(true);

        $this->assertTrue($createPayload['status']);
        $id = $createPayload['data']['id'];
        $row = DB::table('admin_module_records')->where('id', $id)->first();
        $this->assertSame('主线路', $row->title);
        $this->assertSame('enabled', $row->status);
        $this->assertSame([
            'domain' => 'https://line.example.com',
            'line_type' => 'primary',
        ], json_decode($row->business_data, true));

        $status = $this->request('POST', [
            'ids' => [$id],
            'status' => 'disabled',
        ]);
        $statusPayload = $this->controller()
            ->changeStatus($status, '36000')
            ->getData(true);

        $this->assertTrue($statusPayload['status']);
        $this->assertSame('disabled', DB::table('admin_module_records')
            ->where('id', $id)
            ->value('status'));

        $delete = $this->request('DELETE');
        $deletePayload = $this->controller()
            ->deleteRecord($delete, '36000', $id)
            ->getData(true);

        $this->assertTrue($deletePayload['status']);
        $this->assertSame(0, DB::table('admin_module_records')->count());
        $this->assertSame([
            'platform_operations.record.create',
            'platform_operations.status.update',
            'platform_operations.record.delete',
        ], DB::table('admin_audit_logs')->orderBy('id')->pluck('action')->all());
    }

    public function test_transaction_record_can_be_created_and_exported()
    {
        $request = $this->request('POST', [
            'transaction_type' => 'platform_fee_recharge',
            'account_name' => '平台主账户',
            'account_no' => 'INTERNAL-001',
            'amount' => '1000.50',
            'balance_before' => '5000.00',
            'balance_after' => '6000.50',
            'currency' => 'CNY',
            'status' => 'completed',
            'remark' => '测试费用充值',
        ]);
        $payload = $this->controller()
            ->saveRecord($request, '90040')
            ->getData(true);

        $this->assertTrue($payload['status']);
        $this->assertStringStartsWith(
            'PO-90040-',
            $payload['data']['business_no']
        );
        $this->assertEquals(1000.5, (float) DB::table('admin_module_transactions')
            ->value('amount'));

        $export = $this->controller()->export($this->request('GET'), '90040');
        $this->assertStringContainsString(
            'text/csv',
            $export->headers->get('content-type')
        );
        $this->assertStringContainsString(
            $payload['data']['business_no'],
            $export->getContent()
        );
    }

    public function test_legacy_payment_type_actions_use_the_real_table()
    {
        $create = $this->request('POST', [
            'name' => 'Controller Pay',
            'category' => 'online',
            'bonus_ratio' => '2.5',
            'status' => 'enabled',
            'sort_order' => 9,
        ]);
        $payload = $this->controller()
            ->saveRecord($create, '20068')
            ->getData(true);

        $this->assertTrue($payload['status']);
        $id = $payload['data']['id'];
        $this->assertSame('Controller Pay', DB::table('pay_types')
            ->where('id', $id)
            ->value('name'));

        $status = $this->request('POST', [
            'ids' => [$id],
            'status' => 'disabled',
        ]);
        $statusPayload = $this->controller()
            ->changeStatus($status, '20068')
            ->getData(true);
        $this->assertTrue($statusPayload['status']);
        $this->assertSame(0, (int) DB::table('pay_types')
            ->where('id', $id)
            ->value('state'));

        $export = $this->controller()->export($this->request('GET'), '20068');
        $this->assertStringContainsString('Controller Pay', $export->getContent());

        $delete = $this->controller()
            ->deleteRecord($this->request('DELETE'), '20068', $id)
            ->getData(true);
        $this->assertTrue($delete['status']);
        $this->assertSame(0, DB::table('pay_types')->count());
    }

    public function test_platform_fund_report_exports_existing_business_rows()
    {
        Schema::create('recharge', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_no')->nullable();
            $table->string('out_trade_no');
            $table->decimal('real_money', 10, 2);
            $table->integer('state');
            $table->timestamps();
        });
        Schema::create('withdraws', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_no');
            $table->decimal('real_money', 10, 2);
            $table->integer('state');
            $table->timestamps();
        });
        Schema::create('transfer_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_no');
            $table->string('api_type');
            $table->decimal('real_money', 10, 2);
            $table->integer('state');
            $table->timestamps();
        });
        DB::table('recharge')->insert([
            'order_no' => 'R-CONTROLLER-001',
            'out_trade_no' => 'OUT-CONTROLLER-001',
            'real_money' => 88,
            'state' => 1,
        ]);

        $export = $this->controller()->export($this->request('GET'), '31001');

        $this->assertStringContainsString('R-CONTROLLER-001', $export->getContent());
        $this->assertStringContainsString('recharge', $export->getContent());
    }

    public function test_record_and_transaction_exports_respect_business_filters()
    {
        DB::table('admin_module_records')->insert([
            [
                'page_code' => '36000',
                'record_type' => 'record',
                'title' => 'Primary line',
                'status' => 'enabled',
                'sort_order' => 1,
                'business_data' => json_encode([
                    'domain' => 'https://primary.example.com',
                    'line_type' => 'primary',
                ]),
            ],
            [
                'page_code' => '36000',
                'record_type' => 'record',
                'title' => 'Backup line',
                'status' => 'enabled',
                'sort_order' => 2,
                'business_data' => json_encode([
                    'domain' => 'https://backup.example.com',
                    'line_type' => 'backup',
                ]),
            ],
        ]);
        DB::table('admin_module_transactions')->insert([
            [
                'page_code' => '20048',
                'business_no' => 'BANK-A',
                'transaction_type' => 'reconciliation',
                'account_no' => 'ACCOUNT-A',
                'amount' => 100,
                'status' => 'completed',
                'occurred_at' => '2026-07-01 10:00:00',
            ],
            [
                'page_code' => '20048',
                'business_no' => 'BANK-B',
                'transaction_type' => 'reconciliation',
                'account_no' => 'ACCOUNT-B',
                'amount' => 200,
                'status' => 'pending',
                'occurred_at' => '2026-07-02 10:00:00',
            ],
        ]);

        $records = $this->controller()->export(
            $this->request('GET', ['line_type' => 'primary']),
            '36000'
        )->getContent();
        $this->assertStringContainsString('https://primary.example.com', $records);
        $this->assertStringNotContainsString('https://backup.example.com', $records);

        $transactions = $this->controller()->export(
            $this->request('GET', [
                'account_no' => 'ACCOUNT-A',
                'status' => 'completed',
            ]),
            '20048'
        )->getContent();
        $this->assertStringContainsString('ACCOUNT-A', $transactions);
        $this->assertStringNotContainsString('ACCOUNT-B', $transactions);
    }

    private function controller()
    {
        $path = app_path('Admin/Controllers/PlatformOperationsController.php');
        $this->assertFileExists($path);
        require_once $path;

        return new \App\Admin\Controllers\PlatformOperationsController(
            new PlatformOperationsService()
        );
    }

    private function request($method, array $data = [])
    {
        $request = Request::create(
            '/game/tcg/platform-operations',
            $method,
            $data,
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit',
            ]
        );
        $this->app->instance('request', $request);

        return $request;
    }
}
