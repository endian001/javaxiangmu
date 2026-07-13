<?php

namespace Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

class TcgOperationalRecordsSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_operational_record_buttons_are_permission_guarded_and_audited()
    {
        $source = file_get_contents($this->root().'/app/Admin/Controllers/TcgOperationalRecordsController.php');

        $this->assertStringContainsString('use App\Admin\Support\OperationPermission;', $source);

        foreach ([
            'OperationPermission::PLATFORM_OPERATIONS_READ',
            'OperationPermission::PLATFORM_OPERATIONS_WRITE',
            'OperationPermission::PLATFORM_OPERATIONS_DELETE',
            'OperationPermission::PLATFORM_OPERATIONS_EXPORT',
        ] as $ability) {
            $this->assertStringContainsString($ability, $source);
        }

        foreach ([
            'tcg_ops.record.create',
            'tcg_ops.record.update',
            'tcg_ops.record.delete',
            'tcg_ops.record.export',
            'tcg_ops.business.create',
            'tcg_ops.business.update',
            'tcg_ops.business.delete',
            'tcg_ops.business.export',
            'admin_audit_logs',
            'authorizeJson',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_first_business_batch_uses_dedicated_tables_and_fields()
    {
        $source = file_get_contents($this->root().'/app/Admin/Controllers/TcgOperationalRecordsController.php');

        foreach ([
            '20393' => 'tcg_activity_blacklists',
            '24786' => 'tcg_activity_coupons',
            '24800' => 'tcg_activity_multiplier_rules',
            '52000' => 'tcg_player_limit_rules',
            '41002' => 'tcg_user_game_restrictions',
        ] as $code => $table) {
            $this->assertStringContainsString("'".$code."' => [", $source);
            $this->assertStringContainsString("'table' => '".$table."'", $source);
        }

        foreach ([
            'username',
            'user_id',
            'activity_id',
            'reason',
            'game_scope',
            'max_bet',
            'max_payout',
            'restriction_type',
            'starts_at',
            'ends_at',
        ] as $field) {
            $this->assertStringContainsString("'name' => '".$field."'", $source);
        }
    }

    public function test_point_mall_batch_uses_dedicated_tables_fields_and_page_copy()
    {
        $source = file_get_contents($this->root().'/app/Admin/Controllers/TcgOperationalRecordsController.php');

        foreach ([
            '20220' => 'tcg_point_rules',
            '20260' => 'tcg_point_adjustments',
            '20599' => 'tcg_point_mall_products',
            '20530' => 'tcg_point_exchange_orders',
            '31210' => 'tcg_point_reward_records',
        ] as $code => $table) {
            $this->assertStringContainsString("'".$code."' => [", $source);
            $this->assertStringContainsString("'table' => '".$table."'", $source);
        }

        foreach ([
            'rule_code',
            'rule_name',
            'earn_scene',
            'points_per_unit',
            'daily_limit',
            'adjust_type',
            'points_delta',
            'reason_code',
            'product_code',
            'product_name',
            'points_price',
            'stock_total',
            'stock_used',
            'order_no',
            'points_cost',
            'quantity',
            'delivery_info',
            'reward_no',
            'reward_source',
            'points_amount',
            'related_order_no',
        ] as $field) {
            $this->assertStringContainsString("'name' => '".$field."'", $source);
        }

        foreach ([
            '新增积分规则',
            '登记积分调整',
            '新增商城商品',
            '登记兑换申请',
            '登记积分奖励',
            '暂无积分规则',
            '暂无积分调整记录',
            '暂无商城商品',
            '暂无兑换申请',
            '暂无积分奖励记录',
        ] as $copy) {
            $this->assertStringContainsString($copy, $source);
        }
    }

    public function test_business_and_fallback_queries_use_the_correct_scope()
    {
        $source = file_get_contents($this->root().'/app/Admin/Controllers/TcgOperationalRecordsController.php');

        $this->assertStringContainsString("'business' => true", $source);
        $this->assertStringContainsString("'business' => false", $source);
        $this->assertStringContainsString("'table' => 'tcg_operational_records'", $source);
        $this->assertStringContainsString('if (!$schema[\'business\'])', $source);
        $this->assertStringContainsString("->where('page_code', \$code)", $source);
        $this->assertStringContainsString('recordsQuery($request, $code, $schema)->paginate(20)', $source);
        $this->assertStringContainsString('recordsQuery($request, $code, $schema)->get()', $source);
    }

    public function test_operational_record_export_preserves_current_filters()
    {
        $controller = file_get_contents($this->root().'/app/Admin/Controllers/TcgOperationalRecordsController.php');
        $view = file_get_contents($this->root().'/resources/views/admin/tcg-operational-records.blade.php');

        $this->assertStringContainsString("->where('status', \$status)", $controller);
        $this->assertStringContainsString('where(function ($inner) use ($keyword, $fields)', $controller);
        $this->assertStringContainsString('http_build_query(request()->query())', $view);
    }

    public function test_view_renders_schema_fields_and_columns_instead_of_fixed_generic_form()
    {
        $view = file_get_contents($this->root().'/resources/views/admin/tcg-operational-records.blade.php');

        $this->assertStringContainsString('$fields = $schema[\'fields\'];', $view);
        $this->assertStringContainsString('$columns = $schema[\'columns\'];', $view);
        $this->assertStringContainsString('@foreach($fields as $field)', $view);
        $this->assertStringContainsString('@foreach($columns as $column)', $view);
        $this->assertStringContainsString('schemaFields = @json($fields)', $view);
        $this->assertStringContainsString('data-record=', $view);
        $this->assertStringNotContainsString('data-title=', $view);
        $this->assertStringNotContainsString('data-target-key=', $view);
    }

    public function test_view_uses_business_empty_state_action_labels_and_field_select_options()
    {
        $view = file_get_contents($this->root().'/resources/views/admin/tcg-operational-records.blade.php');

        $this->assertStringContainsString('$emptyState = $schema[\'empty_state\']', $view);
        $this->assertStringContainsString('$actionLabel = $schema[\'action_label\']', $view);
        $this->assertStringContainsString('$editLabel = $schema[\'edit_label\']', $view);
        $this->assertStringContainsString('$fieldOptions = $field[\'options\']', $view);
        $this->assertStringContainsString('{{ $emptyState }}', $view);
        $this->assertStringContainsString('{{ $actionLabel }}', $view);
        $this->assertStringContainsString('{{ $editLabel }}', $view);
        $this->assertStringContainsString('item.default', $view);
    }

    public function test_business_operation_migration_creates_dedicated_tables()
    {
        $migration = file_get_contents($this->root().'/database/migrations/2026_07_13_000005_create_tcg_business_operation_tables.php');

        foreach ([
            'tcg_activity_blacklists',
            'tcg_activity_coupons',
            'tcg_activity_multiplier_rules',
            'tcg_player_limit_rules',
            'tcg_user_game_restrictions',
        ] as $table) {
            $this->assertStringContainsString("Schema::create('".$table."'", $migration);
        }
    }

    public function test_point_mall_operation_migration_creates_dedicated_tables()
    {
        $path = $this->root().'/database/migrations/2026_07_13_000007_create_tcg_point_mall_operation_tables.php';

        $this->assertFileExists($path);
        $migration = file_get_contents($path);

        foreach ([
            'tcg_point_rules',
            'tcg_point_adjustments',
            'tcg_point_mall_products',
            'tcg_point_exchange_orders',
            'tcg_point_reward_records',
        ] as $table) {
            $this->assertStringContainsString("Schema::create('".$table."'", $migration);
        }

        foreach ([
            'points_per_unit',
            'points_delta',
            'points_price',
            'points_cost',
            'points_amount',
            'delivery_info',
            'related_order_no',
        ] as $column) {
            $this->assertStringContainsString($column, $migration);
        }
    }

    public function test_tcg_ops_routes_are_registered_before_generic_tcg_fallback()
    {
        $routes = file_get_contents($this->root().'/app/Admin/routes.php');

        foreach ([
            "\$router->get('/tcg/ops/{code}', 'TcgOperationalRecordsController@index');",
            "\$router->post('/tcg/ops/{code}/records', 'TcgOperationalRecordsController@save');",
            "\$router->put('/tcg/ops/{code}/records/{id}', 'TcgOperationalRecordsController@save');",
            "\$router->delete('/tcg/ops/{code}/records/{id}', 'TcgOperationalRecordsController@delete');",
            "\$router->get('/tcg/ops/{code}/export', 'TcgOperationalRecordsController@export');",
        ] as $route) {
            $this->assertStringContainsString($route, $routes);
        }

        $this->assertLessThan(
            strpos($routes, "\$router->get('/tcg/{code}'"),
            strpos($routes, "\$router->get('/tcg/ops/{code}'")
        );
    }
}
