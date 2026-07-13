<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PlatformOperationsPageSourceTest extends TestCase
{
    public function test_page_source_contains_real_management_controls()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/platform-operations.blade.php';
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        foreach ([
            'platform-operations-filter',
            'platform-operations-table',
            'platform-operations-pagination',
            'platform-operation-editor',
            'platform-operation-bulk-delete',
            'platform-operation-import',
            'platform-operation-export',
            'platform-operation-status',
            'data-page-code',
            'csrf_token()',
            'name="csv"',
            'appendRequestField',
            '$statusOptions',
            '$fieldLabels',
            "'payimg' =>",
            "'pay_qrcode' =>",
            "'pay_icon' =>",
            "'title' => '标题'",
            "'site_name' => '站点名称'",
            "'bank_no' => '银行账号'",
            '<textarea',
            '$.ajax({',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringNotContainsString('data._token = csrf', $source);
        $this->assertStringNotContainsString('$.post(url, data)', $source);
        $this->assertStringNotContainsString('演示数据', $source);
        $this->assertStringNotContainsString('功能开发中', $source);
        $this->assertStringNotContainsString('敬请期待', $source);
    }
}
