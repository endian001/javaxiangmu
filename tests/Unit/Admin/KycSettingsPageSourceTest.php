<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class KycSettingsPageSourceTest extends TestCase
{
    public function test_controller_and_view_expose_reference_actions()
    {
        $root = dirname(__DIR__, 3);
        $controllerPath = $root.'/app/Admin/Controllers/KycSettingsController.php';
        $viewPath = $root.'/resources/views/admin/kyc-settings.blade.php';

        $this->assertFileExists($controllerPath);
        $this->assertFileExists($viewPath);

        $controller = file_get_contents($controllerPath);
        $view = file_get_contents($viewPath);

        foreach ([
            'saveField',
            'deleteField',
            'saveRule',
            'deleteRule',
            'saveContent',
            'upload',
            'admin_audit_logs',
        ] as $needle) {
            $this->assertStringContainsString($needle, $controller);
        }

        foreach ([
            '前台安全设置',
            '栏位设置',
            '添加信息栏',
            'KYC验证开关',
            '人工审核(免费)',
            '强制KYC认证设置',
            'KYC提醒弹窗',
            '填写身份信息',
            '提交资料身份',
            '等待审核验证',
            '添加背景图片',
            '复制Web',
        ] as $needle) {
            $this->assertStringContainsString($needle, $view);
        }
    }
}
