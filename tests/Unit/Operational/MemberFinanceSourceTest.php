<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class MemberFinanceSourceTest extends TestCase
{
    private function root()
    {
        return getenv('OPERATIONAL_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_wap_bank_card_route_has_existing_member_controller_method()
    {
        $routes = file_get_contents($this->root().'/routes/web.php');
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Member/PayController.php');

        $this->assertStringContainsString("Route::post('/getBankCardData','Member\\PayController@getBankCardData')", $routes);
        $this->assertStringContainsString('function getBankCardData(', $controller);
    }

    public function test_member_balance_refresh_syncs_and_returns_success_payload()
    {
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Member/PayController.php');
        $method = $this->methodBody($controller, 'getUserBalance');

        $this->assertStringContainsString('Usersmoney::upinfo', $method);
        $this->assertMatchesRegularExpression('/return\s+\$this->returnMsg\(200,\s*\$data/', $method);
    }

    public function test_activity_apply_is_scoped_to_activity_and_checks_availability()
    {
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Member/MemberController.php');
        $method = $this->methodBody($controller, 'doactivity');

        $this->assertStringContainsString("where('activity_id'", $method);
        $this->assertStringContainsString('can_apply', $method);
        $this->assertStringContainsString('state', $method);
        $this->assertStringContainsString('activity application already submitted', $method);
        $this->assertStringContainsString("stripos(\$e->getMessage(), 'Duplicate')", $method);
    }

    public function test_wap_and_api_activity_apply_paths_are_duplicate_safe()
    {
        $wap = file_get_contents($this->root().'/app/Http/Controllers/Wap/IndexController.php');
        $api = file_get_contents($this->root().'/app/Http/Controllers/Api/IndexController.php');
        $promotion = file_get_contents($this->root().'/app/Http/Controllers/Api/PromotionController.php');

        $this->assertStringContainsString('activity_id', $wap);
        $this->assertStringContainsString('activity application already submitted', $wap);
        $this->assertStringContainsString("stripos(\$e->getMessage(), 'Duplicate')", $wap);

        $this->assertStringContainsString('activity_id', $api);
        $this->assertStringContainsString("stripos(\$e->getMessage(), 'Duplicate')", $api);

        $this->assertStringContainsString('activity_id', $promotion);
        $this->assertStringContainsString('Promotion application already submitted', $promotion);
        $this->assertStringContainsString("stripos(\$e->getMessage(), 'Duplicate')", $promotion);
    }

    public function test_api_pay_controller_uses_safe_config_values_for_member_payloads()
    {
        $controller = file_get_contents($this->root().'/app/Http/Controllers/Api/PayController.php');

        $this->assertStringContainsString('function systemConfigValue(', $controller);
        $this->assertStringNotContainsString('$info->value', $controller);
        $this->assertStringNotContainsString('$info_withdraw->value', $controller);
        $this->assertStringNotContainsString('$info_withdrawcashfee->value', $controller);
        $this->assertStringNotContainsString('$info_withdrawfeeusdttrc->value', $controller);
        $this->assertStringContainsString('refresh success', $controller);
    }

    private function methodBody($source, $method)
    {
        $needle = 'function '.$method.'(';
        $start = strpos($source, $needle);
        $this->assertNotFalse($start, "Missing method {$method}");

        $open = strpos($source, '{', $start);
        $this->assertNotFalse($open, "Missing method body for {$method}");

        $depth = 0;
        $length = strlen($source);
        for ($i = $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $open, $i - $open + 1);
                }
            }
        }

        $this->fail("Unclosed method body for {$method}");
    }
}
