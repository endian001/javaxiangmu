<?php

use App\Admin\Support\OperationPermission;
use Illuminate\Database\Seeder;

class AdminOperationPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissionModel = config('admin.database.permissions_model', \Dcat\Admin\Models\Permission::class);

        foreach ($this->permissions() as $index => $permission) {
            $permissionModel::updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'http_method' => null,
                    'http_path' => null,
                    'order' => $index + 1,
                    'parent_id' => 0,
                ]
            );
        }
    }

    protected function permissions()
    {
        return [
            ['slug' => OperationPermission::FINANCE_RECHARGE_PASS, 'name' => 'Finance recharge approve'],
            ['slug' => OperationPermission::FINANCE_RECHARGE_REFUSE, 'name' => 'Finance recharge refuse'],
            ['slug' => OperationPermission::FINANCE_WITHDRAW_PASS, 'name' => 'Finance withdraw approve'],
            ['slug' => OperationPermission::FINANCE_WITHDRAW_REFUSE, 'name' => 'Finance withdraw refuse'],
            ['slug' => OperationPermission::MEMBER_BALANCE_ADJUST, 'name' => 'Member balance adjust'],
            ['slug' => OperationPermission::MEMBER_BALANCE_RECOVER, 'name' => 'Member game balance recover'],
            ['slug' => OperationPermission::AGENT_COMMISSION_SETTLE, 'name' => 'Agent commission settle'],
            ['slug' => OperationPermission::AGENT_APPLY_AUDIT, 'name' => 'Agent apply audit'],
            ['slug' => OperationPermission::ACTIVITY_APPLY_AUDIT, 'name' => 'Activity apply audit'],
            ['slug' => OperationPermission::MEMBER_PASSWORD_RESET, 'name' => 'Member password reset'],
            ['slug' => OperationPermission::MEMBER_AGENT_UPDATE, 'name' => 'Member agent update'],
            ['slug' => OperationPermission::MEMBER_STATUS_UPDATE, 'name' => 'Member status update'],
            ['slug' => OperationPermission::MEMBER_VIP_UPDATE, 'name' => 'Member VIP update'],
            ['slug' => OperationPermission::GAME_LIST_UPDATE, 'name' => 'Game list update'],
            ['slug' => OperationPermission::GAME_PUBLISH_SWITCH, 'name' => 'Game publish switch'],
            ['slug' => OperationPermission::API_PLATFORM_UPDATE, 'name' => 'API platform update'],
            ['slug' => OperationPermission::API_PLATFORM_SWITCH, 'name' => 'API platform switch'],
            ['slug' => OperationPermission::ACTIVITY_CONTENT_UPDATE, 'name' => 'Activity content update'],
            ['slug' => OperationPermission::ACTIVITY_PUBLISH_SWITCH, 'name' => 'Activity publish switch'],
            ['slug' => OperationPermission::OPS_DATA_CLEANUP, 'name' => 'Operations data cleanup preview'],
            ['slug' => OperationPermission::OPS_SITE_SETTING_UPDATE, 'name' => 'Operations site setting update'],
            ['slug' => OperationPermission::PLATFORM_OPERATIONS_READ, 'name' => 'Platform operations read'],
            ['slug' => OperationPermission::PLATFORM_OPERATIONS_WRITE, 'name' => 'Platform operations write'],
            ['slug' => OperationPermission::PLATFORM_OPERATIONS_DELETE, 'name' => 'Platform operations delete'],
            ['slug' => OperationPermission::PLATFORM_OPERATIONS_EXPORT, 'name' => 'Platform operations export'],
        ];
    }
}
