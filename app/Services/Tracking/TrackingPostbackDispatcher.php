<?php

namespace App\Services\Tracking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TrackingPostbackDispatcher
{
    private $config;

    public function __construct(TrackingConfigRepository $config = null)
    {
        $this->config = $config ?: new TrackingConfigRepository();
    }

    public function dispatchForConversion($conversionId, array $conversion, array $attribution = []): void
    {
        if (!Schema::hasTable('promotion_tracking_postback_logs')) {
            return;
        }

        $tracking = $this->decodeJson($attribution['params_json'] ?? null);
        $event = (string) ($conversion['event_name'] ?? '');
        $matchedPlatforms = TrackingPlatformCatalog::platformForTracking($tracking);

        foreach (TrackingPlatformCatalog::platforms() as $platform => $definition) {
            if (!in_array($platform, $matchedPlatforms, true)) {
                continue;
            }

            $platformEvent = TrackingPlatformCatalog::eventName($platform, $event);
            if (!$platformEvent) {
                $this->insertLog($conversionId, $conversion, $attribution, $platform, null, [
                    'status' => 'skipped',
                    'skip_reason' => 'event_not_supported',
                ]);
                continue;
            }

            $request = $this->buildRequest($platform, $definition, $platformEvent, $conversion, $attribution, $tracking);
            $this->insertLog($conversionId, $conversion, $attribution, $platform, $platformEvent, $request);
        }
    }

    public function buildRequest(string $platform, array $definition, string $platformEvent, array $conversion, array $attribution, array $tracking): array
    {
        if (!empty($definition['browser_only'])) {
            return ['status' => 'skipped', 'skip_reason' => 'browser_only'];
        }

        $clickId = TrackingPlatformCatalog::clickIdForPlatform($platform, $tracking);
        $pixelId = TrackingPlatformCatalog::pixelIdForPlatform($platform, $tracking);
        $record = $this->config->platformRecord($platform);
        $settings = $this->config->platformSettings($platform);
        $recordPixelId = $this->config->value($record, 0);
        $token = $this->config->value($record, 1);

        if ($platform === 'facebook') {
            $pixelId = $pixelId ?: $recordPixelId;
            if (!$pixelId || !$token) {
                return ['status' => 'skipped', 'skip_reason' => 'missing_credentials'];
            }

            $payload = [
                'data' => [[
                    'event_name' => $platformEvent,
                    'event_time' => time(),
                    'event_id' => $conversion['event_id'] ?? null,
                    'action_source' => 'website',
                    'event_source_url' => $attribution['landing_url'] ?? $attribution['registration_url'] ?? null,
                    'user_data' => array_filter([
                        'client_ip_address' => $attribution['ip_address'] ?? null,
                        'client_user_agent' => $attribution['user_agent'] ?? null,
                        'fbc' => isset($tracking['fbclid']) ? 'fb.1.'.time().'.'.$tracking['fbclid'] : null,
                    ]),
                    'custom_data' => array_filter([
                        'currency' => $conversion['currency'] ?? 'THB',
                        'value' => (float) ($conversion['amount'] ?? 0),
                        'order_id' => $conversion['order_no'] ?? null,
                    ]),
                ]],
            ];
            $testCode = $this->config->value($record, 2);
            if ($testCode !== '') {
                $payload['test_event_code'] = $testCode;
            }

            return [
                'status' => 'pending',
                'request_method' => 'POST',
                'request_url' => 'https://graph.facebook.com/v17.0/'.rawurlencode($pixelId).'/events',
                'request_headers' => ['Content-Type' => 'application/json'],
                'request_payload' => $payload,
            ];
        }

        if ($platform === 'tiktok') {
            $pixelId = $pixelId ?: $recordPixelId;
            if (!$pixelId || !$token) {
                return ['status' => 'skipped', 'skip_reason' => 'missing_credentials'];
            }

            return [
                'status' => 'pending',
                'request_method' => 'POST',
                'request_url' => 'https://business-api.tiktok.com/open_api/v1.3/event/track/',
                'request_headers' => ['Content-Type' => 'application/json', 'Access-Token' => $this->maskSecret($token)],
                'request_payload' => [
                    'event_source' => 'web',
                    'event_source_id' => $pixelId,
                    'data' => [[
                        'event' => $platformEvent,
                        'event_time' => date('c'),
                        'event_id' => $conversion['event_id'] ?? null,
                        'context' => [
                            'ad' => array_filter(['callback' => $tracking['ttclid'] ?? null]),
                            'page' => array_filter(['url' => $attribution['landing_url'] ?? null, 'referrer' => $attribution['referrer'] ?? null]),
                            'user' => array_filter(['ip' => $attribution['ip_address'] ?? null, 'user_agent' => $attribution['user_agent'] ?? null]),
                        ],
                        'properties' => array_filter([
                            'currency' => $conversion['currency'] ?? 'THB',
                            'value' => (float) ($conversion['amount'] ?? 0),
                            'order_id' => $conversion['order_no'] ?? null,
                        ]),
                    ]],
                ],
            ];
        }

        if ($platform === 'google') {
            $measurementId = $pixelId ?: $recordPixelId;
            $apiSecret = $settings[4] ?? ($this->config->config()['google_api_secret'] ?? '');
            if (!$measurementId || !$apiSecret) {
                return ['status' => 'skipped', 'skip_reason' => 'missing_credentials'];
            }

            return [
                'status' => 'pending',
                'request_method' => 'POST',
                'request_url' => 'https://www.google-analytics.com/mp/collect?measurement_id='.rawurlencode($measurementId).'&api_secret='.rawurlencode($apiSecret),
                'request_headers' => ['Content-Type' => 'application/json'],
                'request_payload' => [
                    'client_id' => $tracking['gclid'] ?? ($attribution['browser_id'] ?? 'server'),
                    'events' => [[
                        'name' => $platformEvent,
                        'params' => array_filter([
                            'transaction_id' => $conversion['order_no'] ?? $conversion['event_id'] ?? null,
                            'currency' => $conversion['currency'] ?? 'THB',
                            'value' => (float) ($conversion['amount'] ?? 0),
                        ]),
                    ]],
                ],
            ];
        }

        if ($platform === 'propellerads') {
            $aid = $settings[0] ?? '';
            $tid = $settings[1] ?? '';
            if (!$clickId) {
                return ['status' => 'skipped', 'skip_reason' => 'missing_click_id'];
            }
            if (!$aid || !$tid) {
                return ['status' => 'skipped', 'skip_reason' => 'missing_credentials'];
            }

            return $this->genericGet('http://ad.propellerads.com/conversion.php', [
                'aid' => $aid,
                'pid' => '',
                'tid' => $tid,
                'visitor_id' => $clickId,
            ]);
        }

        if (!$clickId && !TrackingPlatformCatalog::pixelIdForPlatform($platform, $tracking)) {
            return ['status' => 'skipped', 'skip_reason' => 'missing_click_id'];
        }

        $postbackUrl = $this->postbackUrlFor($platform, $record, $settings);
        if (!$postbackUrl) {
            return ['status' => 'skipped', 'skip_reason' => 'missing_credentials'];
        }

        return $this->genericGet($postbackUrl, [
            'click_id' => $clickId ?: TrackingPlatformCatalog::pixelIdForPlatform($platform, $tracking),
            'event' => $platformEvent,
            'event_id' => $conversion['event_id'] ?? '',
            'amount' => (string) ($conversion['amount'] ?? 0),
            'currency' => $conversion['currency'] ?? 'THB',
            'order_no' => $conversion['order_no'] ?? '',
        ]);
    }

    public function sendRequest(array $request): array
    {
        if (!class_exists('\GuzzleHttp\Client')) {
            return ['status' => 'failed', 'response_status' => null, 'response_body' => 'http_client_missing'];
        }

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 4, 'connect_timeout' => 2, 'http_errors' => false]);
            $method = $request['request_method'] ?? 'GET';
            $options = [
                'headers' => $request['request_headers'] ?? [],
            ];
            if ($method === 'POST') {
                $options['json'] = $request['request_payload'] ?? [];
            }

            $response = $client->request($method, $request['request_url'], $options);
            $status = (int) $response->getStatusCode();

            return [
                'status' => $status >= 200 && $status < 300 ? 'sent' : 'failed',
                'response_status' => $status,
                'response_body' => (string) $response->getBody(),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'response_status' => null, 'response_body' => $e->getMessage()];
        }
    }

    private function insertLog($conversionId, array $conversion, array $attribution, string $platform, $platformEvent, array $request): int
    {
        return DB::table('promotion_tracking_postback_logs')->insertGetId([
            'conversion_event_id' => $conversionId,
            'attribution_id' => $attribution['id'] ?? null,
            'event_id' => $conversion['event_id'] ?? null,
            'platform' => $platform,
            'event_name' => $conversion['event_name'] ?? '',
            'platform_event_name' => $platformEvent,
            'status' => $request['status'] ?? 'pending',
            'skip_reason' => $request['skip_reason'] ?? null,
            'request_method' => $request['request_method'] ?? null,
            'request_url' => $this->maskUrl($request['request_url'] ?? null),
            'request_headers' => $this->json($this->maskHeaders($request['request_headers'] ?? null)),
            'request_payload' => $this->json($request['request_payload'] ?? null),
            'response_status' => null,
            'response_body' => null,
            'attempts' => 0,
            'next_retry_at' => null,
            'sent_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function genericGet(string $url, array $params): array
    {
        $query = http_build_query(array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        }));

        return [
            'status' => 'pending',
            'request_method' => 'GET',
            'request_url' => $url.(strpos($url, '?') === false ? '?' : '&').$query,
            'request_headers' => [],
            'request_payload' => [],
        ];
    }

    private function postbackUrlFor(string $platform, array $record = null, array $settings = []): string
    {
        foreach ($settings as $value) {
            $value = trim((string) $value);
            if (preg_match('/^https?:\/\//i', $value)) {
                return $value;
            }
        }

        if ($record && is_array($record['values'] ?? null)) {
            foreach ($record['values'] as $value) {
                $value = trim((string) $value);
                if (preg_match('/^https?:\/\//i', $value)) {
                    return $value;
                }
            }
        }

        if ($platform === 'voluum' && trim((string) ($settings[0] ?? '')) !== '') {
            return 'https://'.preg_replace('/^https?:\/\//i', '', trim((string) $settings[0])).'/postback';
        }

        if ($platform === 'red_track') {
            $domain = trim((string) ($settings[1] ?? ($settings[0] ?? '')));
            if ($domain !== '') {
                return 'https://'.preg_replace('/^https?:\/\//i', '', $domain).'/postback';
            }
        }

        return '';
    }

    private function decodeJson($value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function json($value)
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function maskUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        return preg_replace('/(access_token|api_secret|token|key)=([^&]+)/i', '$1=***', $url);
    }

    private function maskHeaders($headers)
    {
        if (!is_array($headers)) {
            return $headers;
        }

        foreach ($headers as $key => $value) {
            if (preg_match('/token|authorization|secret|key/i', (string) $key)) {
                $headers[$key] = $this->maskSecret($value);
            }
        }

        return $headers;
    }

    private function maskSecret($value): string
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        if (strlen($value) <= 8) {
            return '***';
        }

        return substr($value, 0, 4).'***'.substr($value, -4);
    }

    private function limitText($value, int $limit)
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
