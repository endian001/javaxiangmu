<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\PlatformOperationsController;
use App\Admin\Services\PlatformOperationsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformOperationsImportTest extends TestCase
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
            $table->decimal('bonus_ratio', 10, 2)->default(0);
            $table->integer('state')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function test_csv_import_creates_record_and_legacy_rows()
    {
        $recordResponse = $this->controller()->import(
            $this->uploadRequest(
                "title,status,sort_order,domain,line_type\n线路 A,enabled,10,https://a.example.com,primary\n"
            ),
            '36000'
        );
        $recordPayload = $recordResponse->getData(true);
        $this->assertTrue($recordPayload['status']);
        $this->assertSame(1, $recordPayload['data']['imported']);
        $record = DB::table('admin_module_records')->first();
        $this->assertSame('线路 A', $record->title);
        $this->assertSame('https://a.example.com', json_decode($record->business_data, true)['domain']);

        $legacyResponse = $this->controller()->import(
            $this->uploadRequest(
                "name,category,bonus_ratio,status,sort_order\nCSV Pay,online,2.5,enabled,9\n"
            ),
            '20068'
        );
        $legacyPayload = $legacyResponse->getData(true);
        $this->assertTrue($legacyPayload['status']);
        $this->assertSame(1, $legacyPayload['data']['imported']);
        $this->assertSame('CSV Pay', DB::table('pay_types')->value('name'));
        $this->assertSame([
            'platform_operations.import',
            'platform_operations.import',
        ], DB::table('admin_audit_logs')->orderBy('id')->pluck('action')->all());
    }

    public function test_report_page_rejects_csv_import()
    {
        $response = $this->controller()->import(
            $this->uploadRequest("business_no,amount\nR001,100\n"),
            '31001'
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['status']);
    }

    public function test_gbk_csv_is_converted_to_utf8()
    {
        $csv = "name,category,bonus_ratio,status,sort_order\n中文支付,在线支付,1.5,enabled,3\n";
        $response = $this->controller()->import(
            $this->uploadRequest(
                mb_convert_encoding($csv, 'GB18030', 'UTF-8'),
                false
            ),
            '20068'
        );

        $this->assertTrue($response->getData(true)['status']);
        $this->assertSame('中文支付', DB::table('pay_types')->value('name'));
        $this->assertSame('在线支付', DB::table('pay_types')->value('category'));
    }

    public function test_invalid_csv_row_rolls_back_the_whole_import()
    {
        $response = $this->controller()->import(
            $this->uploadRequest(
                "name,category,bonus_ratio,status,sort_order\nValid Pay,online,1,enabled,1\n,online,1,enabled,2\n"
            ),
            '20068'
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('pay_types')->count());
        $this->assertSame(0, DB::table('admin_audit_logs')->count());
    }

    private function controller()
    {
        return new PlatformOperationsController(new PlatformOperationsService());
    }

    private function uploadRequest($csv, $withBom = true)
    {
        $path = tempnam(sys_get_temp_dir(), 'platform-import-');
        file_put_contents($path, ($withBom ? "\xEF\xBB\xBF" : '').$csv);
        $file = new UploadedFile($path, 'import.csv', 'text/csv', null, true);
        $request = Request::create(
            '/game/tcg/platform-operations/import',
            'POST',
            [],
            [],
            ['csv' => $file],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit',
            ]
        );
        $this->app->instance('request', $request);

        return $request;
    }
}
