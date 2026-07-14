<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class AgentCommissionSourceTest extends TestCase
{
    public function test_bulk_commission_button_is_permission_guarded_and_scheduled()
    {
        $toolPath = dirname(__DIR__, 3).'/app/Admin/Tools/AgentFanyong.php';
        $kernelPath = dirname(__DIR__, 3).'/app/Console/Kernel.php';
        $this->assertFileExists($toolPath);
        $this->assertFileExists($kernelPath);

        $tool = file_get_contents($toolPath);
        $kernel = file_get_contents($kernelPath);

        $this->assertStringContainsString('OperationPermission::AGENT_COMMISSION_SETTLE', $tool);
        $this->assertStringContainsString('OperationPermission::can', $tool);
        $this->assertStringContainsString('Commands\\AllAgentFanyong::class', $kernel);
        $this->assertStringContainsString("command('AllAgentFanyong')", $kernel);
        $this->assertStringContainsString('everyMinute()', $kernel);
        $this->assertStringContainsString('withoutOverlapping()', $kernel);
    }

    public function test_agent_invite_info_api_defines_qrcode_path_and_bearer_token()
    {
        $controllerPath = dirname(__DIR__, 3).'/app/Http/Controllers/Api/IndexController.php';
        $this->assertFileExists($controllerPath);

        $controller = file_get_contents($controllerPath);

        $this->assertStringContainsString("header('Authorization'", $controller);
        $this->assertStringContainsString("preg_replace('/^Bearer\\s+/i'", $controller);
        $this->assertStringContainsString('$qrcodePath =', $controller);
        $this->assertStringContainsString("'qrcode' => \$this->appPublicUrl() . \$qrcodePath", $controller);
        $this->assertStringNotContainsString('瀵?        $qrcodePath', $controller);
    }
}
