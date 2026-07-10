<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;

class PromotionService
{
    public function visible(array $items, $channel = 'desktop', DateTimeInterface $now = null)
    {
        $now = $now ?: new DateTimeImmutable('now');
        $channel = $channel === 'mobile' ? 'mobile' : 'desktop';

        $visible = array_values(array_filter($items, function ($item) use ($channel, $now) {
            if ((int) $this->value($item, 'state', 0) !== 1) {
                return false;
            }
            if ($channel === 'mobile' && (int) $this->value($item, 'app_state', 1) !== 1) {
                return false;
            }
            $startsAt = $this->date($this->value($item, 'starts_at'));
            if ($startsAt && $startsAt > $now) {
                return false;
            }
            $endsAt = $this->date($this->value($item, 'ends_at'));
            if ($endsAt && $endsAt < $now) {
                return false;
            }

            return true;
        }));

        usort($visible, function ($a, $b) {
            $sort = (int) $this->value($b, 'sort_order', 0) <=> (int) $this->value($a, 'sort_order', 0);
            if ($sort !== 0) {
                return $sort;
            }

            return (int) $this->value($b, 'id', 0) <=> (int) $this->value($a, 'id', 0);
        });

        return $visible;
    }

    public function popup(array $items, $channel = 'desktop', DateTimeInterface $now = null)
    {
        foreach ($this->visible($items, $channel, $now) as $item) {
            if ((int) $this->value($item, 'is_popup', 0) === 1) {
                return $item;
            }
        }

        return null;
    }

    private function value($item, $key, $default = null)
    {
        if (is_array($item)) {
            return array_key_exists($key, $item) ? $item[$key] : $default;
        }
        if (is_object($item)) {
            return isset($item->{$key}) ? $item->{$key} : $default;
        }

        return $default;
    }

    private function date($value)
    {
        if (!$value) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        try {
            return new DateTimeImmutable((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
