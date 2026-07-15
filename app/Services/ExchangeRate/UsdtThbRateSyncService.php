<?php

namespace App\Services\ExchangeRate;

use App\Models\SystemConfig;

class UsdtThbRateSyncService
{
    const SOURCE_NAME = 'coingecko';
    const SOURCE_URL = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=thb';
    const RATE_KEY = 'usdt_rate';
    const SOURCE_KEY = 'usdt_rate_source';
    const UPDATED_AT_KEY = 'usdt_rate_last_sync_at';
    const STATUS_KEY = 'usdt_rate_last_sync_status';
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

    public function sync()
    {
        try {
            $payload = $this->fetchPayload(self::SOURCE_URL);
            return $this->syncFromPayload($payload, self::SOURCE_NAME, self::SOURCE_URL);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'source' => self::SOURCE_NAME,
                'source_url' => self::SOURCE_URL,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncFromPayload(array $payload, $sourceName = self::SOURCE_NAME, $sourceUrl = self::SOURCE_URL)
    {
        try {
            $rate = $this->extractRate($payload);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'source' => $sourceName,
                'source_url' => $sourceUrl,
                'error' => $e->getMessage(),
            ];
        }

        $formattedRate = $this->formatRate($rate);
        $this->storeRate($formattedRate, $sourceName, $sourceUrl);

        return [
            'status' => 'updated',
            'rate' => $formattedRate,
            'source' => $sourceName,
            'source_url' => $sourceUrl,
        ];
    }

    public function extractRate(array $payload)
    {
        if (!isset($payload['tether']) || !is_array($payload['tether']) || !array_key_exists('thb', $payload['tether'])) {
            throw new \RuntimeException('CoinGecko payload missing tether.thb');
        }

        $rate = (float) $payload['tether']['thb'];
        if ($rate <= 0) {
            throw new \RuntimeException('CoinGecko tether.thb must be greater than zero');
        }

        return $rate;
    }

    public function fetchPayload($url = self::SOURCE_URL)
    {
        $url = trim((string) $url);
        if ($url === '') {
            throw new \RuntimeException('Missing exchange rate source URL');
        }

        $responseBody = $this->httpGet($url);
        $payload = json_decode($responseBody, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Invalid exchange rate response: ' . json_last_error_msg());
        }

        return $payload;
    }

    protected function storeRate($rate, $sourceName, $sourceUrl)
    {
        $now = date('Y-m-d H:i:s');

        SystemConfig::updateOrCreate(['key' => self::RATE_KEY], ['key' => self::RATE_KEY, 'value' => $rate]);
        SystemConfig::updateOrCreate(['key' => self::SOURCE_KEY], ['key' => self::SOURCE_KEY, 'value' => $sourceName]);
        SystemConfig::updateOrCreate(['key' => self::UPDATED_AT_KEY], ['key' => self::UPDATED_AT_KEY, 'value' => $now]);
        SystemConfig::updateOrCreate(['key' => self::STATUS_KEY], ['key' => self::STATUS_KEY, 'value' => 'success']);
        SystemConfig::updateOrCreate(['key' => 'usdt_rate_last_sync_source_url'], ['key' => 'usdt_rate_last_sync_source_url', 'value' => $sourceUrl]);
    }

    protected function formatRate($rate)
    {
        return number_format((float) $rate, 4, '.', '');
    }

    protected function httpGet($url)
    {
        if (class_exists('\GuzzleHttp\Client')) {
            $client = new \GuzzleHttp\Client([
                'timeout' => 8,
                'connect_timeout' => 3,
                'http_errors' => false,
            ]);

            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ],
            ]);

            $status = (int) $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('Exchange rate source returned HTTP ' . $status);
            }

            return (string) $response->getBody();
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: ' . self::USER_AGENT,
                ],
            ]);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                throw new \RuntimeException($error ?: 'curl_request_failed');
            }
            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('Exchange rate source returned HTTP ' . $status);
            }

            return (string) $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: " . self::USER_AGENT . "\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $error = error_get_last();
            throw new \RuntimeException($error['message'] ?? 'http_request_failed');
        }

        return (string) $body;
    }
}
