<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class GameManagementPageSourceTest extends TestCase
{
    public function test_game_management_view_contains_real_page_actions()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/game-management.blade.php';
        $this->assertFileExists($path);
        $source = file_get_contents($path);

        foreach ([
            'game-management-filter',
            'game-management-record-modal',
            'game-management-save',
            'game-management-status',
            'game-management-delete',
            'game-management-bulk-delete',
            'game-management-import',
            'game-management-export',
            '$records->links()',
            '$page[\'fields\']',
            '$page[\'columns\']',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }

        $this->assertStringNotContainsString('tcg-shell', $source);
        $this->assertStringNotContainsString('功能开发中', $source);
        $this->assertStringNotContainsString('alert(\"', $source);
    }

    public function test_export_link_bypasses_dcat_pjax()
    {
        $path = dirname(__DIR__, 3).'/resources/views/admin/game-management.blade.php';
        $source = file_get_contents($path);

        $this->assertStringContainsString(
            'class="btn btn-success game-management-export" target="_blank" rel="noopener"',
            $source
        );
    }
}
