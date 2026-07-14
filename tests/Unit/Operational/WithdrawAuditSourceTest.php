<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class WithdrawAuditSourceTest extends TestCase
{
    private function root()
    {
        return getenv('OPERATIONAL_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_withdraw_pass_and_refuse_are_permission_guarded_and_unified_audited()
    {
        foreach ([
            'Pass' => 'FINANCE_WITHDRAW_PASS',
            'Refuse' => 'FINANCE_WITHDRAW_REFUSE',
        ] as $action => $permission) {
            $source = file_get_contents($this->root().'/app/Admin/Actions/Grid/Withdraw/'.$action.'.php');

            $this->assertStringContainsString('OperationPermission::assert(OperationPermission::'.$permission.')', $source);
            $this->assertStringContainsString('OperationPermission::can(OperationPermission::'.$permission, $source);
            $this->assertStringContainsString('lockForUpdate()', $source);
            $this->assertStringContainsString('UserOperateLog::insertLog', $source);
            $this->assertStringContainsString('OpsChangeAudit::writeAdminAudit', $source);
            $this->assertStringContainsString('before_balance', $source);
            $this->assertStringContainsString('after_balance', $source);
        }
    }
}
