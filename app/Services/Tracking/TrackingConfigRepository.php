<?php

namespace App\Services\Tracking;

use Illuminate\Support\Facades\Storage;

class TrackingConfigRepository
{
    private const PIXEL_CONFIG_PATH = 'tcg/pixel-config.json';

    public function payload(): array
    {
        try {
            if (!Storage::disk('local')->exists(self::PIXEL_CONFIG_PATH)) {
                return [];
            }

            $payload = json_decode(Storage::disk('local')->get(self::PIXEL_CONFIG_PATH), true);

            return is_array($payload) ? $payload : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function tabState(string $tab): array
    {
        $payload = $this->payload();
        $state = $payload['benchmark']['tabs'][$tab] ?? [];

        return is_array($state) ? $state : [];
    }

    public function recordsFor(string $tab): array
    {
        $state = $this->tabState($tab);
        $records = array_values(is_array($state['records'] ?? null) ? $state['records'] : []);

        return array_values(array_filter($records, function ($record) {
            return is_array($record) && ($record['enabled'] ?? true) !== false;
        }));
    }

    public function settingsFor(string $tab): array
    {
        $state = $this->tabState($tab);

        return array_values(is_array($state['settings'] ?? null) ? $state['settings'] : []);
    }

    public function firstEnabledRecord(string $tab)
    {
        $records = $this->recordsFor($tab);

        return $records ? $records[0] : null;
    }

    public function platformRecord(string $platform)
    {
        $definition = TrackingPlatformCatalog::platforms()[$platform] ?? [];
        $tab = $definition['config_tab'] ?? '';

        return $tab === '' ? null : $this->firstEnabledRecord($tab);
    }

    public function platformSettings(string $platform): array
    {
        $definition = TrackingPlatformCatalog::platforms()[$platform] ?? [];
        $tab = $definition['config_tab'] ?? '';

        return $tab === '' ? [] : $this->settingsFor($tab);
    }

    public function config(): array
    {
        $payload = $this->payload();

        return is_array($payload['config'] ?? null) ? $payload['config'] : [];
    }

    public function value(array $record = null, int $index = 0): string
    {
        if (!$record || !is_array($record['values'] ?? null)) {
            return '';
        }

        return trim((string) ($record['values'][$index] ?? ''));
    }
}
