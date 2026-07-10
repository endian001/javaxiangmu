<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\PlatformOperationsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperationsLegacyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'game_lists',
            'pay_types',
            'pay_setting',
            'banks',
            'code_pay',
            'usdt_pay',
            'agent_settlements',
            'articles',
            'articlescate',
            'recharge',
            'withdraws',
            'transfer_logs',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('game_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('platform_name');
            $table->string('name');
            $table->integer('site_state')->default(1);
            $table->integer('app_state')->default(1);
            $table->timestamps();
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
        Schema::create('banks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bank_name');
            $table->integer('state')->default(1);
            $table->timestamps();
        });
        Schema::create('pay_setting', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('bank_id');
            $table->string('bank_no');
            $table->string('bank_owner');
            $table->string('bank_address')->nullable();
            $table->string('info')->nullable();
            $table->integer('state')->default(1);
            $table->timestamps();
        });
        Schema::create('code_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category')->nullable();
            $table->string('mch_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
        Schema::create('usdt_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category')->nullable();
            $table->string('wallet_address')->nullable();
            $table->integer('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('agent_settlements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('type')->default(0);
            $table->decimal('member_fs', 8, 2)->default(0);
            $table->integer('required_new_members')->nullable();
            $table->integer('state')->default(1);
            $table->timestamps();
        });
        Schema::create('articlescate', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('cateid')->nullable();
            $table->longText('content')->nullable();
            $table->integer('stor')->default(0);
            $table->timestamps();
        });
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
    }

    public function test_legacy_pages_read_and_normalize_real_business_tables()
    {
        $service = new PlatformOperationsService();
        $this->assertTrue(method_exists($service, 'legacyRows'));

        DB::table('game_lists')->insert([
            ['platform_name' => 'PG', 'name' => 'Game A', 'site_state' => 1, 'app_state' => 1],
            ['platform_name' => 'PG', 'name' => 'Game B', 'site_state' => 1, 'app_state' => 0],
        ]);
        DB::table('pay_types')->insert([
            'name' => 'Online Pay',
            'category' => 'online',
            'bonus_ratio' => 2.5,
            'state' => 1,
            'sort_order' => 10,
        ]);
        $bankId = DB::table('banks')->insertGetId([
            'bank_name' => 'Test Bank',
            'state' => 1,
        ]);
        DB::table('pay_setting')->insert([
            'bank_id' => $bankId,
            'bank_no' => '62220001',
            'bank_owner' => 'WAKUANG',
            'state' => 1,
        ]);
        DB::table('code_pay')->insert([
            'category' => 'qr',
            'mch_id' => 'M001',
            'status' => 1,
        ]);
        DB::table('usdt_pay')->insert([
            'category' => 'trc20',
            'wallet_address' => 'TTEST',
            'status' => 1,
            'sort_order' => 1,
        ]);
        DB::table('agent_settlements')->insert([
            'name' => 'Standard',
            'type' => 1,
            'member_fs' => 0.8,
            'required_new_members' => 3,
            'state' => 1,
        ]);
        $categoryId = DB::table('articlescate')->insertGetId(['name' => 'Help']);
        DB::table('articles')->insert([
            'name' => 'How to deposit',
            'cateid' => $categoryId,
            'content' => 'Content',
            'stor' => 5,
        ]);

        $this->assertSame(1, $service->legacyRows('31018')->total());
        $this->assertSame(1, $service->legacyRows('20068')->total());
        $this->assertSame(3, $service->legacyRows('20028')->total());
        $this->assertSame(1, $service->legacyRows('21150')->total());
        $this->assertSame(1, $service->legacyRows('12650')->total());

        $bankRows = $service->legacyRows('20032');
        $this->assertSame(1, $bankRows->total());
        $this->assertSame('Test Bank', $bankRows->items()[0]->bank_name);
        $this->assertSame('62220001', $bankRows->items()[0]->bank_no);
    }

    public function test_platform_fund_report_combines_real_money_tables()
    {
        $service = new PlatformOperationsService();
        $this->assertTrue(method_exists($service, 'reportRows'));

        DB::table('recharge')->insert([
            'order_no' => 'R001',
            'out_trade_no' => 'OUT001',
            'real_money' => 100,
            'state' => 1,
        ]);
        DB::table('withdraws')->insert([
            'order_no' => 'W001',
            'real_money' => 30,
            'state' => 1,
        ]);
        DB::table('transfer_logs')->insert([
            'order_no' => 'T001',
            'api_type' => 'PG',
            'real_money' => 20,
            'state' => 1,
        ]);

        $rows = $service->reportRows('31001');
        $this->assertSame(3, $rows->total());
        $this->assertEqualsCanonicalizing([
            'recharge',
            'withdrawal',
            'game_transfer',
        ], array_map(function ($row) {
            return $row->transaction_type;
        }, $rows->items()));
    }

    public function test_legacy_payment_type_can_be_saved_status_changed_and_deleted()
    {
        $service = new PlatformOperationsService();
        foreach (['saveLegacyRecord', 'changeLegacyStatus', 'deleteLegacyRecord'] as $method) {
            $this->assertTrue(method_exists($service, $method));
        }

        $saved = $service->saveLegacyRecord('20068', [
            'name' => 'New Pay',
            'category' => 'online',
            'bonus_ratio' => '1.5',
            'status' => 'enabled',
            'sort_order' => 6,
        ]);
        $this->assertSame('pay_types', $saved['source_table']);
        $this->assertSame('New Pay', DB::table('pay_types')->value('name'));

        $updated = $service->changeLegacyStatus(
            '20068',
            [$saved['id']],
            'disabled'
        );
        $this->assertSame(1, $updated);
        $this->assertSame(0, (int) DB::table('pay_types')->value('state'));

        $deleted = $service->deleteLegacyRecord('20068', $saved['id']);
        $this->assertSame(1, $deleted);
        $this->assertSame(0, DB::table('pay_types')->count());
    }
}
