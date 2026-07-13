<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class PlatformOperationsCatalogTest extends TestCase
{
    public function test_it_exposes_all_first_wave_pages_with_business_specific_contracts()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Services/PlatformOperationsService.php';
        $this->assertFileExists($path);

        require_once $path;
        $service = new \App\Admin\Services\PlatformOperationsService();
        $pages = $service->pages();

        $this->assertSame([
            '90510' => '平台站点配置',
            '36000' => '域名线路管理',
            '31018' => '游戏厂商设置',
            '90401' => '平台功能配置',
            '24001' => '提现风控配置',
            '20068' => '平台支付管理',
            '20028' => '支付账号设置',
            '20500' => '代理政策设置',
            '21150' => '平台佣金设置',
            '12650' => '帮助中心设置',
            '2981' => '简讯发送设置',
            '800003' => '飞行员服务',
            '31001' => '平台资金详情',
            '20048' => '银行对账报表',
            '20032' => '银行账号明细',
            '90040' => '平台费用充值',
        ], array_map(function (array $page) {
            return $page['title'];
        }, $pages));

        foreach ($pages as $code => $page) {
            $this->assertSame((string) $code, $page['code']);
            $this->assertNotEmpty($page['module']);
            $this->assertContains($page['mode'], [
                'settings',
                'records',
                'legacy',
                'report',
                'transactions',
            ]);
            $this->assertGreaterThanOrEqual(2, count($page['filters']));
            $this->assertGreaterThanOrEqual(4, count($page['columns']));
            $this->assertNotEmpty($page['actions']);
            if (!in_array($page['mode'], ['settings', 'report'], true)) {
                $this->assertContains('import', $page['actions'], "Page {$code} must support CSV import");
            }
        }
    }

    public function test_it_filters_record_inputs_to_each_page_schema()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Services/PlatformOperationsService.php';
        $this->assertFileExists($path);
        require_once $path;

        $service = new \App\Admin\Services\PlatformOperationsService();

        $this->assertSame([
            'title' => '线路 A',
            'status' => '1',
            'sort_order' => 20,
            'business_data' => [
                'domain' => 'https://line-a.example.com',
                'line_type' => 'primary',
            ],
        ], $service->filterRecordInput('36000', [
            'title' => ' 线路 A ',
            'status' => '1',
            'sort_order' => '20',
            'domain' => 'https://line-a.example.com',
            'line_type' => 'primary',
            'merchant_key' => 'must-not-pass',
        ]));
    }

    public function test_it_exposes_page_specific_status_options_and_rejects_invalid_values()
    {
        $path = dirname(__DIR__, 3).'/app/Admin/Services/PlatformOperationsService.php';
        $this->assertFileExists($path);
        require_once $path;

        $service = new \App\Admin\Services\PlatformOperationsService();

        $this->assertSame([
            'enabled' => '启用',
            'disabled' => '停用',
        ], $service->statusOptions('20028'));
        $this->assertSame([
            'pending' => '待处理',
            'processing' => '处理中',
            'completed' => '已完成',
            'failed' => '失败',
        ], $service->statusOptions('20048'));

        $this->assertSame('enabled', $service->normalizeStatus('20028', 'enabled'));
        $this->assertSame('completed', $service->normalizeStatus('20048', 'completed'));

        try {
            $service->normalizeStatus('20028', 'completed');
            $this->fail('Enable/disable pages should not accept transaction statuses.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('状态', $exception->getMessage());
        }

        try {
            $service->normalizeStatus('20048', 'enabled');
            $this->fail('Transaction pages should not accept enable/disable statuses.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('状态', $exception->getMessage());
        }
    }
}
