<?php

namespace Tests\Unit\Promotion;

use App\Services\PromotionService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PromotionServiceTest extends TestCase
{
    public function test_it_filters_by_channel_and_active_dates_then_sorts()
    {
        $service = new PromotionService();
        $now = new DateTimeImmutable('2026-07-11 12:00:00');

        $visible = $service->visible([
            ['id' => 1, 'state' => 1, 'app_state' => 1, 'sort_order' => 10, 'starts_at' => null, 'ends_at' => null],
            ['id' => 2, 'state' => 1, 'app_state' => 0, 'sort_order' => 30, 'starts_at' => null, 'ends_at' => null],
            ['id' => 3, 'state' => 1, 'app_state' => 1, 'sort_order' => 20, 'starts_at' => '2026-07-12 00:00:00', 'ends_at' => null],
            ['id' => 4, 'state' => 1, 'app_state' => 1, 'sort_order' => 40, 'starts_at' => null, 'ends_at' => '2026-07-10 23:59:59'],
        ], 'mobile', $now);

        $this->assertSame([1], array_column($visible, 'id'));
    }

    public function test_it_selects_the_highest_sorted_popup()
    {
        $service = new PromotionService();
        $now = new DateTimeImmutable('2026-07-11 12:00:00');

        $popup = $service->popup([
            ['id' => 1, 'state' => 1, 'app_state' => 1, 'sort_order' => 10, 'is_popup' => 1],
            ['id' => 2, 'state' => 1, 'app_state' => 1, 'sort_order' => 30, 'is_popup' => 1],
            ['id' => 3, 'state' => 1, 'app_state' => 1, 'sort_order' => 50, 'is_popup' => 0],
        ], 'desktop', $now);

        $this->assertSame(2, $popup['id']);
    }
}
