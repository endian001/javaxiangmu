<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class AgentCommissionAuditSourceTest extends TestCase
{
    public function test_single_agent_commission_settlement_writes_unified_admin_audit()
    {
        $actionPath = dirname(__DIR__, 3).'/app/Admin/Actions/Grid/User/Fanyong.php';
        $this->assertFileExists($actionPath);

        $action = file_get_contents($actionPath);

        $this->assertStringContainsString('OperationPermission::AGENT_COMMISSION_SETTLE', $action);
        $this->assertStringContainsString('OperationPermission::can', $action);
        $this->assertStringContainsString('UserOperateLog::insertLog', $action);
        $this->assertStringContainsString('OpsChangeAudit::writeAdminAudit', $action);
        $this->assertStringContainsString("'agent.commission.settle'", $action);
        $this->assertStringContainsString("'before_balance' => \$beforeBalance", $action);
        $this->assertStringContainsString("'after_balance' => (float) \$user->balance", $action);
        $this->assertStringContainsString("'log_ids' => \$logs->pluck('id')->all()", $action);
    }
}
