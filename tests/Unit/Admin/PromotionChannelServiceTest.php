<?php

namespace Tests\Unit\Admin;

use App\Admin\Services\PromotionChannelService;
use PHPUnit\Framework\TestCase;

class PromotionChannelServiceTest extends TestCase
{
    public function test_it_exposes_all_non_pixel_promotion_pages()
    {
        $this->assertTrue(class_exists(PromotionChannelService::class));

        $service = new PromotionChannelService();

        $this->assertSame([
            '280000',
            '21160',
            '280004',
            '280008',
            '280015',
            '280012',
        ], array_map('strval', array_keys($service->pages())));
        $this->assertArrayNotHasKey('12535', $service->pages());
    }

    public function test_page_definitions_include_real_actions_and_fields()
    {
        $service = new PromotionChannelService();

        $this->assertContains('新增主站投放链接', $service->page('280000')['buttons']);
        $this->assertContains('批量设置', $service->page('21160')['buttons']);
        $this->assertContains('评论区设置', $service->page('280004')['fields']);
        $this->assertContains('描述元数据', $service->page('280008')['fields']);
        $this->assertContains('定时发送', $service->page('280015')['buttons']);
        $this->assertContains('导出', $service->page('280012')['buttons']);
    }
}
