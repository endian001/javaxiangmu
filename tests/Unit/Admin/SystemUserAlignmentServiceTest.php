<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\SystemUserAlignmentService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SystemUserAlignmentServiceTest extends TestCase
{
    public function test_it_normalizes_and_deduplicates_brand_names()
    {
        $service = new SystemUserAlignmentService();

        $this->assertSame(
            ['主品牌', '代理品牌'],
            $service->normalizeBrands(' 主品牌, 代理品牌,主品牌, ')
        );
    }

    public function test_it_validates_ip_addresses()
    {
        $service = new SystemUserAlignmentService();

        $this->assertSame('127.0.0.1', $service->validateIp(' 127.0.0.1 '));
        $this->assertSame('2001:db8::1', $service->validateIp('2001:db8::1'));
    }

    public function test_it_rejects_invalid_ip_addresses()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('IP 地址格式不正确');

        (new SystemUserAlignmentService())->validateIp('999.1.1.1');
    }

    public function test_it_filters_permission_ids_against_real_permissions()
    {
        $service = new SystemUserAlignmentService();

        $this->assertSame(
            [2, 5],
            $service->filterPermissionIds([2, '5', 999, 2, 'bad'], [1, 2, 5])
        );
    }

    public function test_it_only_allows_safe_task_types()
    {
        $service = new SystemUserAlignmentService();

        $this->assertTrue($service->isAllowedTaskType('health_check'));
        $this->assertTrue($service->isAllowedTaskType('cache_clear'));
        $this->assertFalse($service->isAllowedTaskType('shell'));
        $this->assertFalse($service->isAllowedTaskType('migrate_fresh'));
    }

    public function test_it_builds_traceable_task_numbers()
    {
        $service = new SystemUserAlignmentService();

        $this->assertSame(
            'ADM-20260710120000-ABC123',
            $service->makeTaskNumber(
                new DateTimeImmutable('2026-07-10 12:00:00'),
                'abc123'
            )
        );
    }
}
