<?php

namespace Tests\Unit\Admin;

use App\Admin\Support\OperationPermission;
use PHPUnit\Framework\TestCase;

class PlatformOperationsPermissionTest extends TestCase
{
    public function test_platform_operations_permissions_are_defined_and_seeded()
    {
        foreach ([
            'PLATFORM_OPERATIONS_READ',
            'PLATFORM_OPERATIONS_WRITE',
            'PLATFORM_OPERATIONS_DELETE',
            'PLATFORM_OPERATIONS_EXPORT',
        ] as $constant) {
            $this->assertTrue(defined(OperationPermission::class.'::'.$constant));
        }

        $seeder = file_get_contents(
            dirname(__DIR__, 3).'/database/seeds/AdminOperationPermissionSeeder.php'
        );
        foreach ([
            'PLATFORM_OPERATIONS_READ',
            'PLATFORM_OPERATIONS_WRITE',
            'PLATFORM_OPERATIONS_DELETE',
            'PLATFORM_OPERATIONS_EXPORT',
        ] as $constant) {
            $this->assertStringContainsString(
                'OperationPermission::'.$constant,
                $seeder
            );
        }
    }

    public function test_controller_checks_permissions_for_each_operation_type()
    {
        $controller = file_get_contents(
            dirname(__DIR__, 3).'/app/Admin/Controllers/PlatformOperationsController.php'
        );

        foreach ([
            'OperationPermission::PLATFORM_OPERATIONS_READ',
            'OperationPermission::PLATFORM_OPERATIONS_WRITE',
            'OperationPermission::PLATFORM_OPERATIONS_DELETE',
            'OperationPermission::PLATFORM_OPERATIONS_EXPORT',
        ] as $needle) {
            $this->assertStringContainsString($needle, $controller);
        }
    }
}
