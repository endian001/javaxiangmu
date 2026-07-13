<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class PaymentApiSourceTest extends TestCase
{
    public function test_app_usdt_recharge_uses_usdt_pay_accounts()
    {
        $path = dirname(__DIR__, 3).'/app/Http/Controllers/Api/AppController.php';
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertStringContainsString('use App\\Models\\UsdtPay;', $source);
        $this->assertStringContainsString('UsdtPay::where', $source);
        $this->assertStringContainsString('$datas[\'usdt_rate\'] = $UsdtPay->exchange_rate', $source);

        $method = substr($source, strpos($source, 'public function usdt_pay'));
        $method = substr($method, 0, strpos($method, 'public function cgpay_pay'));
        $this->assertStringNotContainsString('CodePay::where', $method);
    }

    public function test_app_withdraw_uses_operational_risk_controls()
    {
        $path = dirname(__DIR__, 3).'/app/Http/Controllers/Api/AppController.php';
        $source = file_get_contents($path);
        $method = substr($source, strpos($source, 'public function post_drawing'));
        $method = substr($method, 0, strpos($method, 'public function post_update_bank_info'));

        $this->assertStringContainsString("SystemConfig::getValue('daily_withdraw_times')", $method);
        $this->assertStringContainsString("SystemConfig::getValue('withdraw_begin_time')", $method);
        $this->assertStringContainsString("SystemConfig::getValue('withdraw_end_time')", $method);
        $this->assertStringContainsString("SystemConfig::getValue('withdraw_fee')", $method);
        $this->assertStringContainsString('Hash::check($password, $user->paypwd)', $method);
        $this->assertStringContainsString("SystemConfig::getValue('withdraw_cash_fee')", $method);
        $this->assertStringContainsString("SystemConfig::getValue('withdraw_fee_usdt_erc')", $method);
        $this->assertStringContainsString('DB::transaction(function () use', $method);
        $this->assertStringContainsString('$lockedUser->balance -= $amount;', $method);
        $this->assertStringNotContainsString('UserCard::find($data[\'bankid\'])', $method);
    }

    public function test_cgpay_uses_pay_type_merchant_settings()
    {
        $root = dirname(__DIR__, 3);
        $service = file_get_contents($root.'/app/Services/PayService.php');
        $app = file_get_contents($root.'/app/Http/Controllers/Api/AppController.php');
        $migration = file_get_contents($root.'/database/migrations/2026_07_13_000004_extend_pay_types_operational_fields.php');

        $this->assertStringContainsString('use App\\Models\\PayType;', $service);
        $this->assertStringContainsString('function cgpay($bill_no, $money, $channel = null)', $service);
        $this->assertStringContainsString('function cgpayMerchantConfig($channel = null)', $service);
        $this->assertStringContainsString('merchant_no', $service);
        $this->assertStringContainsString('merchant_key', $service);
        $this->assertStringContainsString('merchant_url', $service);
        $this->assertStringContainsString("\$this->PayService->cgpay(\$datas['out_trade_no'], \$datas['amount'], \$CodePay)", $app);

        foreach (['category', 'bonus_ratio', 'merchant_no', 'merchant_key', 'merchant_url', 'merchant_identifier', 'merchant_code'] as $column) {
            $this->assertStringContainsString($column, $migration);
        }
    }
}
