<?php

namespace App\Admin\Services;

use InvalidArgumentException;

class KycSettingsService
{
    private const PAGES = [
        '610110' => [
            'module' => 'fields',
            'title' => '用户信息管理',
            'summary' => '管理前台个人资料与身份验证栏位、显示规则和安全打码方式。',
        ],
        '290000' => [
            'module' => 'rules',
            'title' => 'KYC功能配置',
            'summary' => '配置 KYC 审核方式、强制验证人群、触发场景和证件资料要求。',
        ],
        '290004' => [
            'module' => 'content',
            'title' => '前台内容配置',
            'summary' => '按平台、语系和验证步骤配置 KYC 前台文案、图片与按钮。',
        ],
    ];

    private const BOOLEAN_FIELDS = [
        'kyc_enabled',
        'frontend_visible',
        'required',
        'player_editable',
        'unique_value',
        'status',
        'is_default',
        'enabled',
        'force_enabled',
        'tag_internal',
        'tag_operation',
        'scenario_login',
        'scenario_deposit',
        'scenario_withdraw',
        'scenario_game',
        'require_id_type',
        'require_id_number',
        'require_withdraw_name',
        'require_document_images',
        'force_verify',
    ];

    public function pages(): array
    {
        return self::PAGES;
    }

    public function page($code): array
    {
        $code = (string) $code;
        if (!isset(self::PAGES[$code])) {
            throw new InvalidArgumentException('KYC 页面不存在');
        }

        return self::PAGES[$code];
    }

    public function defaultFields(): array
    {
        $rows = [
            ['nickname', '昵称', 'social', 'input', 1, 0, 1, 0, 0, 'any', 1, 255, 'plain', []],
            ['withdraw_name', '提款人姓名', 'identity', 'input', 1, 0, 0, 0, 0, 'any', 1, 255, 'masked', []],
            ['address', '地址', 'identity', 'input', 0, 0, 0, 0, 0, 'any', 1, 255, 'masked', []],
            ['permanent_address', '永久地址', 'identity', 'input', 0, 0, 0, 0, 0, 'any', 1, 255, 'masked', []],
            ['region', '地区', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', []],
            ['qq', 'QQ', 'social', 'input', 0, 0, 0, 1, 0, 'any', 5, 10, 'masked', []],
            ['wechat', '微信ID', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 99, 'masked', []],
            ['line_id', 'Line ID', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['facebook_id', 'Facebook ID', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['apple_id', 'Apple ID', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 99, 'masked', []],
            ['google', 'Google', 'social', 'input', 1, 1, 0, 1, 0, 'any', 0, 0, 'masked', []],
            ['state', '状态', 'identity', 'select', 0, 0, 1, 0, 0, 'any', 0, 0, 'masked', []],
            ['postal_code', '邮政编码', 'identity', 'input', 0, 0, 0, 0, 0, 'any', 1, 6, 'masked', []],
            ['whatsapp', 'WhatsApp', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['kakao', 'Kakao', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 99, 'masked', []],
            ['zalo', 'Zalo', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['telegram', 'Telegram', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['viber', 'Viber', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['twitter', 'Twitter', 'social', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['email', '电子邮件', 'social', 'input', 1, 0, 1, 1, 0, 'email', 1, 255, 'masked', []],
            ['birth_date', '生日', 'identity', 'date', 0, 0, 1, 0, 0, 'date', 10, 10, 'partial', []],
            ['birthplace', '出生地', 'identity', 'input', 0, 0, 0, 0, 0, 'any', 1, 255, 'masked', []],
            ['nationality', '国籍', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', []],
            ['id_number', '身份证/CPF', 'identity', 'input', 0, 0, 1, 1, 0, 'any', 1, 20, 'masked', []],
            ['gender', '性别', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', ['女', '男']],
            ['occupation', '职业', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', []],
            ['income_source', '收入来源', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', []],
            ['marital_status', '婚姻状况', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', ['单身', '已婚', '分居', '离异', '丧偶']],
            ['id_type', '身份证类型', 'identity', 'select', 0, 0, 0, 0, 0, 'any', 0, 0, 'masked', ['身份证', '护照', 'DNI', 'CPF']],
            ['phone', '手机号码', 'social', 'input', 1, 1, 0, 1, 0, 'any', 11, 11, 'masked', []],
        ];

        $fields = [];
        foreach ($rows as $position => $row) {
            $fields[] = [
                'field_key' => $row[0],
                'default_label' => $row[1],
                'custom_label' => null,
                'category' => $row[2],
                'input_type' => $row[3],
                'kyc_enabled' => $row[4],
                'frontend_visible' => $row[5],
                'required' => $row[6],
                'player_editable' => $row[7],
                'unique_value' => $row[8],
                'format_rule' => $row[9],
                'min_length' => $row[10],
                'max_length' => $row[11],
                'mask_mode' => $row[12],
                'options' => $row[13],
                'position' => $position + 1,
                'status' => 1,
            ];
        }

        return $fields;
    }

    public function filterField(array $values): array
    {
        $result = [];
        $textFields = [
            'field_key',
            'default_label',
            'custom_label',
        ];
        foreach ($textFields as $field) {
            if (array_key_exists($field, $values)) {
                $result[$field] = $this->text($values[$field], $field === 'field_key' ? 80 : 191);
            }
        }

        if (array_key_exists('category', $values)) {
            $category = (string) $values['category'];
            $result['category'] = in_array($category, ['identity', 'social'], true)
                ? $category
                : 'identity';
        }
        if (array_key_exists('input_type', $values)) {
            $inputType = (string) $values['input_type'];
            $result['input_type'] = in_array($inputType, ['input', 'select', 'date'], true)
                ? $inputType
                : 'input';
        }
        if (array_key_exists('format_rule', $values)) {
            $format = (string) $values['format_rule'];
            $result['format_rule'] = in_array($format, ['any', 'email', 'date'], true)
                ? $format
                : 'any';
        }
        if (array_key_exists('mask_mode', $values)) {
            $mask = (string) $values['mask_mode'];
            $result['mask_mode'] = in_array($mask, ['plain', 'partial', 'masked'], true)
                ? $mask
                : 'masked';
        }

        foreach (['min_length', 'max_length', 'position'] as $field) {
            if (array_key_exists($field, $values)) {
                $result[$field] = $this->clamp($values[$field], 0, $field === 'position' ? 10000 : 255);
            }
        }
        foreach (self::BOOLEAN_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $result[$field] = $this->boolean($values[$field]);
            }
        }
        if (array_key_exists('options', $values)) {
            $result['options'] = $this->stringList($values['options'], 300);
        }

        return $result;
    }

    public function filterRule(array $values): array
    {
        $result = [];
        if (array_key_exists('name', $values)) {
            $result['name'] = $this->text($values['name'], 100);
        }
        if (array_key_exists('review_mode', $values)) {
            $mode = (string) $values['review_mode'];
            $result['review_mode'] = in_array($mode, ['manual', 'automatic'], true)
                ? $mode
                : 'manual';
        }
        if (array_key_exists('image_count', $values)) {
            $result['image_count'] = $this->clamp($values['image_count'], 1, 6);
        }
        if (array_key_exists('image_titles', $values)) {
            $result['image_titles'] = $this->stringList($values['image_titles'], 80, 6);
        }
        if (array_key_exists('position', $values)) {
            $result['position'] = $this->clamp($values['position'], 0, 10000);
        }
        foreach (self::BOOLEAN_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $result[$field] = $this->boolean($values[$field]);
            }
        }

        return $result;
    }

    public function contentSteps(): array
    {
        return [1, 2, 3, 4];
    }

    public function filterContent(array $values): array
    {
        $step = isset($values['step']) ? (int) $values['step'] : 0;
        if (!in_array($step, $this->contentSteps(), true)) {
            throw new InvalidArgumentException('KYC 前台步骤不存在');
        }

        $result = ['step' => $step];
        $platform = (string) ($values['platform'] ?? 'mobile');
        $result['platform'] = in_array($platform, ['mobile', 'web', 'app'], true)
            ? $platform
            : 'mobile';
        $result['language'] = strtoupper($this->text($values['language'] ?? 'EN', 10));

        foreach ([
            'title' => 191,
            'body' => 20000,
            'button_text' => 191,
            'secondary_button_text' => 191,
            'background_image' => 1000,
        ] as $field => $limit) {
            if (array_key_exists($field, $values)) {
                $result[$field] = $this->text($values[$field], $limit);
            }
        }
        foreach (['force_verify', 'status'] as $field) {
            if (array_key_exists($field, $values)) {
                $result[$field] = $this->boolean($values[$field]);
            }
        }

        return $result;
    }

    private function text($value, int $limit): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $limit);
    }

    private function boolean($value): int
    {
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true) ? 1 : 0;
    }

    private function clamp($value, int $minimum, int $maximum): int
    {
        return max($minimum, min($maximum, (int) $value));
    }

    private function stringList($values, int $limit, int $maxItems = 200): array
    {
        if (!is_array($values)) {
            $values = preg_split('/[\r\n,]+/', (string) $values);
        }

        $result = [];
        foreach (array_slice($values, 0, $maxItems) as $value) {
            $text = $this->text($value, $limit);
            if ($text !== '') {
                $result[] = $text;
            }
        }

        return array_values($result);
    }
}
