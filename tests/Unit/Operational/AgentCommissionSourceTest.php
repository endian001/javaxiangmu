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
}
