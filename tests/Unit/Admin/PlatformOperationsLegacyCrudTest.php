<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\PlatformOperationsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperationsLegacyCrudTest extends TestCase
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
            $table->decimal('bonus_ratio', 10, 2)->default(0);
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
            $table->string('bank_address')->default('');
            $table->string('info')->nullable();
            $table->integer('state')->default(1);
            $table->timestamps();
        });
        Schema::create('code_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pay_type_id')->nullable();
            $table->string('category')->nullable();
            $table->string('mch_id')->nullable();
            $table->string('key')->nullable();
            $table->string('content')->nullable();
            $table->string('payimg')->nullable();
            $table->integer('status')->default(1);
            $table->string('remark')->nullable();
            $table->string('download_name')->default('');
            $table->string('download_url')->default('');
            $table->decimal('min_price', 10, 2)->default(0);
            $table->decimal('max_price', 10, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('usdt_pay', function (Blueprint $table) {
            $table->increments('id');
            $table->string('category')->nullable();
            $table->string('wallet_address')->nullable();
            $table->string('pay_qrcode')->nullable();
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->decimal('min_price', 10, 2)->default(1);
            $table->decimal('max_price', 10, 2)->default(10000);
            $table->decimal('bonus_ratio', 10, 2)->default(0);
            $table->string('pay_icon')->nullable();
            $table->integer('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('agent_settlements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('type')->default(1);
            $table->decimal('realperson', 6, 2)->default(0);
            $table->decimal('electron', 6, 2)->default(0);
            $table->decimal('joker', 6, 2)->default(0);
            $table->decimal('sport', 6, 2)->default(0);
            $table->decimal('fish', 6, 2)->default(0);
            $table->decimal('lottery', 6, 2)->default(0);
            $table->decimal('e_sport', 6, 2)->default(0);
            $table->decimal('member_fs', 8, 2)->default(0);
            $table->integer('required_new_members')->default(0);
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
            $table->string('name')->nullable();
            $table->string('enname')->nullable();
            $table->integer('cateid')->nullable();
            $table->longText('content')->nullable();
            $table->longText('encontent')->nullable();
            $table->integer('stor')->nullable();
            $table->timestamps();
        });
    }

    public function test_business_specific_legacy_filters_are_applied()
    {
        $service = new PlatformOperationsService();
        DB::table('game_lists')->insert([
            ['platform_name' => 'PG', 'name' => 'Game A'],
            ['platform_name' => 'JILI', 'name' => 'Game B'],
        ]);
        DB::table('pay_types')->insert([
            ['name' => 'Online', 'category' => 'online'],
            ['name' => 'Offline', 'category' => 'offline'],
        ]);
        $bankA = DB::table('banks')->insertGetId(['bank_name' => 'Bank A']);
        $bankB = DB::table('banks')->insertGetId(['bank_name' => 'Bank B']);
        DB::table('pay_setting')->insert([
            ['bank_id' => $bankA, 'bank_no' => 'A1', 'bank_owner' => 'Owner A'],
            ['bank_id' => $bankB, 'bank_no' => 'B1', 'bank_owner' => 'Owner B'],
        ]);
        DB::table('code_pay')->insert(['category' => 'qr', 'mch_id' => 'C1']);
        DB::table('usdt_pay')->insert(['category' => 'trc20', 'wallet_address' => 'T1']);
        DB::table('agent_settlements')->insert([
            ['name' => 'Rebate', 'type' => 1],
            ['name' => 'Commission', 'type' => 2],
        ]);
        $help = DB::table('articlescate')->insertGetId(['name' => 'Help']);
        $news = DB::table('articlescate')->insertGetId(['name' => 'News']);
        DB::table('articles')->insert([
            ['name' => 'Deposit', 'cateid' => $help],
            ['name' => 'Notice', 'cateid' => $news],
        ]);

        $this->assertSame(1, $service->legacyRows('31018', ['platform_name' => 'PG'])->total());
        $this->assertSame(1, $service->legacyRows('20068', ['category' => 'online'])->total());
        $this->assertSame(1, $service->legacyRows('20028', ['account_type' => 'code'])->total());
        $this->assertSame(1, $service->legacyRows('21150', ['type' => '2'])->total());
        $this->assertSame(1, $service->legacyRows('12650', ['category' => 'Help'])->total());
        $this->assertSame(1, $service->legacyRows('20032', ['bank_name' => 'Bank B'])->total());
    }

    public function test_payment_account_crud_supports_all_real_tables()
    {
        $service = new PlatformOperationsService();
        $bankId = DB::table('banks')->insertGetId(['bank_name' => 'CRUD Bank']);

        $bank = $service->saveLegacyRecord('20028', [
            'account_type' => 'bank',
            'bank_id' => $bankId,
            'bank_no' => '62220001',
            'bank_owner' => 'Owner',
            'status' => 'enabled',
        ]);
        $this->assertSame('', DB::table('pay_setting')->where('id', $bank['id'])->value('bank_address'));

        $code = $service->saveLegacyRecord('20028', [
            'account_type' => 'code',
            'category' => 'qr',
            'mch_id' => 'M001',
            'payimg' => 'pay/qr.png',
            'status' => 'enabled',
        ]);
        $this->assertSame('', DB::table('code_pay')->where('id', $code['id'])->value('download_name'));
        $this->assertSame('', DB::table('code_pay')->where('id', $code['id'])->value('download_url'));
        $this->assertEquals(0.0, (float) DB::table('code_pay')->where('id', $code['id'])->value('min_price'));
        $this->assertEquals(0.0, (float) DB::table('code_pay')->where('id', $code['id'])->value('max_price'));

        $usdt = $service->saveLegacyRecord('20028', [
            'account_type' => 'usdt',
            'category' => 'trc20',
            'wallet_address' => 'TTEST',
            'pay_qrcode' => 'pay/usdt-qr.png',
            'pay_icon' => 'pay/usdt-icon.png',
            'status' => 'enabled',
        ]);

        $service->saveLegacyRecord('20028', [
            'account_type' => 'code',
            'category' => 'updated',
            'mch_id' => 'M002',
            'payimg' => 'pay/qr-updated.png',
            'status' => 'enabled',
        ], 'code_pay:'.$code['id']);
        $this->assertSame('updated', DB::table('code_pay')->where('id', $code['id'])->value('category'));

        $updated = $service->changeLegacyStatus('20028', [
            'pay_setting:'.$bank['id'],
            'code_pay:'.$code['id'],
            'usdt_pay:'.$usdt['id'],
        ], 'disabled');
        $this->assertSame(3, $updated);

        $this->assertSame(1, $service->deleteLegacyRecord('20028', 'pay_setting:'.$bank['id']));
        $this->assertSame(1, $service->deleteLegacyRecord('20028', 'code_pay:'.$code['id']));
        $this->assertSame(1, $service->deleteLegacyRecord('20028', 'usdt_pay:'.$usdt['id']));
    }

    public function test_payment_account_crud_uses_defaults_for_null_form_values()
    {
        $service = new PlatformOperationsService();

        $code = $service->saveLegacyRecord('20028', [
            'account_type' => 'code',
            'pay_type_id' => null,
            'category' => 'qr-null-defaults',
            'mch_id' => 'M-NULL',
            'payimg' => null,
            'download_name' => null,
            'download_url' => null,
            'min_price' => null,
            'max_price' => null,
            'status' => 'disabled',
        ]);

        $row = DB::table('code_pay')->where('id', $code['id'])->first();
        $this->assertSame('', $row->download_name);
        $this->assertSame('', $row->download_url);
        $this->assertEquals(0.0, (float) $row->min_price);
        $this->assertEquals(0.0, (float) $row->max_price);
    }

    public function test_enabled_payment_accounts_require_and_persist_frontend_image_fields()
    {
        $service = new PlatformOperationsService();

        try {
            $service->saveLegacyRecord('20028', [
                'account_type' => 'code',
                'category' => 'qr',
                'mch_id' => 'M-NO-IMG',
                'status' => 'enabled',
            ]);
            $this->fail('Enabled code pay account should require payimg.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('payimg', $exception->getMessage());
        }

        $code = $service->saveLegacyRecord('20028', [
            'account_type' => 'code',
            'category' => 'qr',
            'mch_id' => 'M-WITH-IMG',
            'payimg' => 'pay/code.png',
            'status' => 'enabled',
        ]);
        $this->assertSame('pay/code.png', DB::table('code_pay')->where('id', $code['id'])->value('payimg'));

        try {
            $service->saveLegacyRecord('20028', [
                'account_type' => 'usdt',
                'category' => 'trc20',
                'wallet_address' => 'T-NO-IMG',
                'status' => 'enabled',
            ]);
            $this->fail('Enabled USDT account should require QR code and icon.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('pay_qrcode', $exception->getMessage());
        }

        $usdt = $service->saveLegacyRecord('20028', [
            'account_type' => 'usdt',
            'category' => 'trc20',
            'wallet_address' => 'T-WITH-IMG',
            'pay_qrcode' => 'pay/usdt-qr.png',
            'pay_icon' => 'pay/usdt-icon.png',
            'status' => 'enabled',
        ]);
        $row = DB::table('usdt_pay')->where('id', $usdt['id'])->first();
        $this->assertSame('pay/usdt-qr.png', $row->pay_qrcode);
        $this->assertSame('pay/usdt-icon.png', $row->pay_icon);
    }

    public function test_commission_help_and_bank_account_crud_use_real_tables()
    {
        $service = new PlatformOperationsService();
        $commission = $service->saveLegacyRecord('21150', [
            'name' => 'Standard',
            'type' => 2,
            'member_fs' => 0.8,
            'status' => 'enabled',
        ]);
        $service->saveLegacyRecord('21150', [
            'name' => 'Updated',
            'type' => 2,
            'member_fs' => 1.0,
            'status' => 'enabled',
        ], $commission['id']);
        $this->assertSame('Updated', DB::table('agent_settlements')->where('id', $commission['id'])->value('name'));
        $this->assertSame(1, $service->changeLegacyStatus('21150', [$commission['id']], 'disabled'));
        $this->assertSame(1, $service->deleteLegacyRecord('21150', $commission['id']));

        $categoryId = DB::table('articlescate')->insertGetId(['name' => 'Help']);
        $article = $service->saveLegacyRecord('12650', [
            'name' => 'How to deposit',
            'cateid' => $categoryId,
            'content' => 'Content',
            'stor' => 1,
        ]);
        $service->saveLegacyRecord('12650', [
            'name' => 'How to withdraw',
            'cateid' => $categoryId,
            'content' => 'Updated',
            'stor' => 2,
        ], $article['id']);
        $this->assertSame('How to withdraw', DB::table('articles')->where('id', $article['id'])->value('name'));
        $this->assertSame(1, $service->deleteLegacyRecord('12650', $article['id']));

        $bankId = DB::table('banks')->insertGetId(['bank_name' => 'Account Bank']);
        $account = $service->saveLegacyRecord('20032', [
            'bank_id' => $bankId,
            'bank_no' => '62220002',
            'bank_owner' => 'Owner',
            'status' => 'enabled',
        ]);
        $service->saveLegacyRecord('20032', [
            'bank_id' => $bankId,
            'bank_no' => '62220003',
            'bank_owner' => 'Updated Owner',
            'status' => 'enabled',
        ], $account['id']);
        $this->assertSame('Updated Owner', DB::table('pay_setting')->where('id', $account['id'])->value('bank_owner'));
        $this->assertSame(1, $service->changeLegacyStatus('20032', [$account['id']], 'disabled'));
        $this->assertSame(1, $service->deleteLegacyRecord('20032', $account['id']));
    }
}
