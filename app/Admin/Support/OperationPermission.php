<?php

namespace App\Admin\Support;

use Dcat\Admin\Admin;

class OperationPermission
{
    const FINANCE_RECHARGE_PASS = 'finance.recharge.pass';
    const FINANCE_RECHARGE_REFUSE = 'finance.recharge.refuse';
    const FINANCE_WITHDRAW_PASS = 'finance.withdraw.pass';
    const FINANCE_WITHDRAW_REFUSE = 'finance.withdraw.refuse';
    const MEMBER_BALANCE_ADJUST = 'member.balance.adjust';
    const MEMBER_BALANCE_RECOVER = 'member.balance.recover';
    const AGENT_COMMISSION_SETTLE = 'agent.commission.settle';
    const AGENT_APPLY_AUDIT = 'agent.apply.audit';
    const ACTIVITY_APPLY_AUDIT = 'activity.apply.audit';
    const MEMBER_PASSWORD_RESET = 'member.password.reset';
    const MEMBER_AGENT_UPDATE = 'member.agent.update';
    const MEMBER_STATUS_UPDATE = 'member.status.update';
    const MEMBER_VIP_UPDATE = 'member.vip.update';
    const GAME_LIST_UPDATE = 'game.list.update';
    const GAME_PUBLISH_SWITCH = 'game.publish.switch';
    const API_PLATFORM_UPDATE = 'api.platform.update';
    const API_PLATFORM_SWITCH = 'api.platform.switch';
    const ACTIVITY_CONTENT_UPDATE = 'activity.content.update';
    const ACTIVITY_PUBLISH_SWITCH = 'activity.publish.switch';
    const OPS_DATA_CLEANUP = 'ops.data.cleanup';
    const OPS_SITE_SETTING_UPDATE = 'ops.site.setting.update';
    const PLATFORM_OPERATIONS_READ = 'platform.operations.read';
    const PLATFORM_OPERATIONS_WRITE = 'platform.operations.write';
    const PLATFORM_OPERATIONS_DELETE = 'platform.operations.delete';
    const PLATFORM_OPERATIONS_EXPORT = 'platform.operations.export';

    public static function can($ability, $user = null)
    {
        if (config('admin.permission.enable') === false) {
            return true;
        }

        $user = $user ?: Admin::user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isAdministrator') && $user->isAdministrator()) {
            return true;
        }

        return method_exists($user, 'can') && $user->can($ability);
    }

    public static function assert($ability)
    {
        if (! static::can($ability)) {
            throw new \RuntimeException('permission denied: '.$ability);
        }
    }
}
