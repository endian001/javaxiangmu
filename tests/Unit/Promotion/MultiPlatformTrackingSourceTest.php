<?php

namespace Tests\Unit\Promotion;

use PHPUnit\Framework\TestCase;

class MultiPlatformTrackingSourceTest extends TestCase
{
    private function root()
    {
        return getenv('PROMOTION_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    private function read($path)
    {
        $fullPath = $this->root().'/'.$path;
        $this->assertFileExists($fullPath);

        return file_get_contents($fullPath);
    }

    public function test_tracking_migration_adds_attribution_conversion_and_postback_tables()
    {
        $source = $this->read('database/migrations/2026_07_14_000002_create_multi_platform_tracking_tables.php');

        foreach ([
            'promotion_tracking_attributions',
            'promotion_tracking_conversions',
            'promotion_tracking_postback_logs',
            'attribution_key',
            'browser_id',
            'session_id',
            'params_json',
            'click_ids_json',
            'platforms_json',
            'event_id',
            'event_name',
            'standard_event',
            'is_first_deposit',
            'platform',
            'status',
            'skip_reason',
            'request_url',
            'request_payload',
            'response_status',
            'next_retry_at',
        ] as $needle) {
            $this->assertStringContainsString($needle, $source);
        }
    }

    public function test_platform_catalog_contains_documented_platform_keys_and_event_mappings()
    {
        $source = $this->read('app/Services/Tracking/TrackingPlatformCatalog.php');

        foreach ([
            'facebook',
            'tiktok',
            'kwai',
            'google',
            'gtm',
            'appsflyer',
            'bigo',
            'okspin',
            'voluum',
            'traffic_factory',
            'propellerads',
            'red_track',
            'outbrain',
            'kadam',
            'phoenix_ads',
            'mgskyads',
            'devils_tracker',
            'macan_studio',
            'routerhub',
            'egw',
            'fortune',
            'keitaro',
            'revosurge',
            'resiliencemedia',
            'snapchat',
        ] as $platform) {
            $this->assertStringContainsString("'".$platform."'", $source, $platform);
        }

        foreach ([
            'kwaiPixelBaseCode',
            'bigoPixelId',
            'pixel_click_id',
            'oks_pixel_id',
            'tfTracker',
            'visitor_id',
            'rtCid',
            'obclid',
            'kadam_id',
            'phxCid',
            'mgsClickId',
            'devilsClickId',
            'macanClickId',
            'rbclickid',
            'egwId',
            'fortune',
            'clickId',
            'keitaroClickId',
            'clickid',
            'revosurge',
            'rmClickId',
            'ad_app_token',
            'gps_adid',
            'adid',
        ] as $key) {
            $this->assertStringContainsString("'".$key."'", $source, $key);
        }

        foreach ([
            'firstOpen',
            'registerSubmit',
            'register',
            'depositSubmit',
            'firstDepositArrival',
            'startTrial',
            'deposit',
            'redeposit',
            'withdraw',
            'CompleteRegistration',
            'CompletePayment',
            'EVENT_COMPLETE_REGISTRATION',
            'EVENT_FIRST_DEPOSIT',
            'EVENT_PURCHASE',
            'SIGN_UP',
            'PURCHASE',
        ] as $event) {
            $this->assertStringContainsString($event, $source, $event);
        }
    }

    public function test_tracking_service_handles_user_binding_first_deposit_and_legacy_event_records()
    {
        $service = $this->read('app/Services/Tracking/MultiPlatformTrackingService.php');
        $legacy = $this->read('app/Services/PromotionPixelEventService.php');

        foreach ([
            'recordBrowserEvent',
            'recordRechargeArrival',
            'upsertAttribution',
            'bindAttributionToUser',
            'createConversionEvent',
            'dispatchForConversion',
            'promotion_event_records',
            'firstDepositArrival',
            'startTrial',
            'redeposit',
            'isFirstSuccessfulRecharge',
            'stableEventId',
        ] as $needle) {
            $this->assertStringContainsString($needle, $service, $needle);
        }

        $this->assertStringContainsString('MultiPlatformTrackingService', $legacy);
        $this->assertStringContainsString('recordBrowserEvent($request)', $legacy);
        $this->assertStringContainsString('recordRechargeArrival($recharge, $context)', $legacy);
    }

    public function test_postback_dispatcher_records_real_send_status_or_skip_reason_per_platform()
    {
        $dispatcher = $this->read('app/Services/Tracking/TrackingPostbackDispatcher.php');
        $config = $this->read('app/Services/Tracking/TrackingConfigRepository.php');

        foreach ([
            'missing_credentials',
            'missing_click_id',
            'event_not_supported',
            'pending',
            'sent',
            'failed',
            'skipped',
            'promotion_tracking_postback_logs',
            'buildRequest',
            'sendRequest',
            'request_url',
            'request_payload',
            'response_body',
        ] as $needle) {
            $this->assertStringContainsString($needle, $dispatcher, $needle);
        }

        foreach ([
            'PIXEL_CONFIG_PATH',
            'tcg/pixel-config.json',
            'recordsFor',
            'settingsFor',
            'firstEnabledRecord',
        ] as $needle) {
            $this->assertStringContainsString($needle, $config, $needle);
        }
    }
}
