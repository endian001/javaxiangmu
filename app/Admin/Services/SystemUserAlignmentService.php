<?php

namespace App\Admin\Services;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class SystemUserAlignmentService
{
    private const TASK_TYPES = [
        'health_check' => '系统健康检查',
        'cache_clear' => '清理应用缓存',
        'view_clear' => '清理视图缓存',
        'route_clear' => '清理路由缓存',
    ];

    public function normalizeBrands($brands): array
    {
        if (is_array($brands)) {
            $parts = $brands;
        } else {
            $parts = preg_split('/[,，;\r\n]+/u', (string) $brands) ?: [];
        }

        $normalized = [];
        foreach ($parts as $brand) {
            if (is_array($brand) || is_object($brand)) {
                continue;
            }

            $brand = trim(strip_tags((string) $brand));
            if ($brand === '' || in_array($brand, $normalized, true)) {
                continue;
            }

            $normalized[] = mb_substr($brand, 0, 100);
        }

        return array_slice($normalized, 0, 50);
    }

    public function validateIp($ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException('IP 地址格式不正确');
        }

        return $ip;
    }

    public function filterPermissionIds(array $requested, array $available): array
    {
        $availableIds = [];
        foreach ($available as $id) {
            if (is_numeric($id)) {
                $availableIds[(int) $id] = true;
            }
        }

        $filtered = [];
        foreach ($requested as $id) {
            if (!is_numeric($id)) {
                continue;
            }

            $id = (int) $id;
            if (!isset($availableIds[$id]) || in_array($id, $filtered, true)) {
                continue;
            }

            $filtered[] = $id;
        }

        sort($filtered);

        return $filtered;
    }

    public function allowedTaskTypes(): array
    {
        return self::TASK_TYPES;
    }

    public function isAllowedTaskType($taskType): bool
    {
        return array_key_exists((string) $taskType, self::TASK_TYPES);
    }

    public function makeTaskNumber(
        DateTimeInterface $time = null,
        $entropy = null
    ): string {
        $time = $time ?: new DateTimeImmutable();
        $suffix = preg_replace(
            '/[^A-F0-9]/',
            '',
            strtoupper((string) ($entropy ?: bin2hex(random_bytes(3))))
        );
        $suffix = substr(str_pad($suffix, 6, '0'), 0, 6);

        return 'ADM-'.$time->format('YmdHis').'-'.$suffix;
    }
}
