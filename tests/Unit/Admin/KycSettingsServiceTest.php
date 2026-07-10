<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\KycSettingsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class KycSettingsServiceTest extends TestCase
{
    public function test_it_exposes_the_three_kyc_pages()
    {
        $service = new KycSettingsService();

        $this->assertSame(
            ['610110', '290000', '290004'],
            array_map('strval', array_keys($service->pages()))
        );
        $this->assertSame('fields', $service->page('610110')['module']);
        $this->assertSame('rules', $service->page('290000')['module']);
        $this->assertSame('content', $service->page('290004')['module']);
    }

    public function test_default_fields_match_the_reference_management_columns()
    {
        $service = new KycSettingsService();
        $fields = $service->defaultFields();
        $byKey = [];

        foreach ($fields as $field) {
            $byKey[$field['field_key']] = $field;
        }

        $this->assertArrayHasKey('nickname', $byKey);
        $this->assertArrayHasKey('withdraw_name', $byKey);
        $this->assertArrayHasKey('id_number', $byKey);
        $this->assertArrayHasKey('id_type', $byKey);
        $this->assertArrayHasKey('phone', $byKey);
        $this->assertSame('identity', $byKey['id_number']['category']);
        $this->assertSame('social', $byKey['phone']['category']);
        $this->assertContains(
            $byKey['phone']['mask_mode'],
            ['plain', 'partial', 'masked']
        );
    }

    public function test_it_filters_field_and_rule_payloads()
    {
        $service = new KycSettingsService();

        $field = $service->filterField([
            'custom_label' => '<b>身份证号码</b>',
            'input_type' => 'input',
            'category' => 'identity',
            'format_rule' => 'any',
            'min_length' => -10,
            'max_length' => 9999,
            'unknown' => 'drop-me',
        ]);
        $this->assertSame('身份证号码', $field['custom_label']);
        $this->assertSame(0, $field['min_length']);
        $this->assertSame(255, $field['max_length']);
        $this->assertArrayNotHasKey('unknown', $field);

        $rule = $service->filterRule([
            'name' => '<i>默认</i>',
            'review_mode' => 'manual',
            'image_count' => 99,
            'image_titles' => ['front', 'back', '<b>third</b>'],
            'scenario_login' => '1',
        ]);
        $this->assertSame('默认', $rule['name']);
        $this->assertSame(6, $rule['image_count']);
        $this->assertSame(['front', 'back', 'third'], $rule['image_titles']);
        $this->assertSame(1, $rule['scenario_login']);
    }

    public function test_it_rejects_unknown_pages_and_content_steps()
    {
        $service = new KycSettingsService();

        $this->expectException(InvalidArgumentException::class);
        $service->page('12535');
    }

    public function test_content_steps_are_limited_to_the_four_reference_steps()
    {
        $service = new KycSettingsService();

        $this->assertSame([1, 2, 3, 4], $service->contentSteps());

        $this->expectException(InvalidArgumentException::class);
        $service->filterContent(['step' => 5]);
    }
}
