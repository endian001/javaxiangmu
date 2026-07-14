<?php

namespace App\Services\Tracking;

class TrackingPlatformCatalog
{
    private const CAPTURE_KEYS = [
        'affiliateCode',
        'agentCode',
        'invite_code',
        'pid',
        'linkId',
        'fbPixelId',
        'tiktokPixelId',
        'kwai_pixel_id',
        'kwaiPixelBaseCode',
        'gtagId',
        'gtmId',
        'bigoPixelId',
        'pixel_click_id',
        'oks_pixel_id',
        'fbclid',
        'ttclid',
        'gclid',
        'cid',
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
        'af_app_id',
        'appsflyer_id',
        'advertising_id',
        'oaid',
        'idfa',
        'idfv',
        'ad_app_token',
        'gps_adid',
        'adid',
    ];

    private const CLICK_ID_KEYS = [
        'fbclid',
        'ttclid',
        'gclid',
        'cid',
        'tfTracker',
        'visitor_id',
        'rtCid',
        'obclid',
        'kadam_id',
        'pixel_click_id',
        'phxCid',
        'mgsClickId',
        'devilsClickId',
        'macanClickId',
        'rbclickid',
        'clickId',
        'keitaroClickId',
        'clickid',
        'rmClickId',
    ];

    public static function captureKeys(): array
    {
        return self::CAPTURE_KEYS;
    }

    public static function clickIdKeys(): array
    {
        return self::CLICK_ID_KEYS;
    }

    public static function platforms(): array
    {
        return [
            'facebook' => [
                'label' => 'Facebook/Meta',
                'config_tab' => 'facebook',
                'parameter_keys' => ['fbPixelId', 'fbclid'],
                'click_keys' => ['fbclid'],
                'pixel_keys' => ['fbPixelId'],
                'events' => [
                    'firstOpen' => 'firstOpen',
                    'registerSubmit' => 'registerSubmit',
                    'register' => 'CompleteRegistration',
                    'depositSubmit' => 'InitiateCheckout',
                    'firstDepositArrival' => 'firstDepositArrival',
                    'startTrial' => 'StartTrial',
                    'deposit' => 'Purchase',
                    'redeposit' => 'redeposit',
                    'withdraw' => 'withdraw',
                ],
            ],
            'tiktok' => [
                'label' => 'TikTok',
                'config_tab' => 'tiktok',
                'parameter_keys' => ['tiktokPixelId', 'ttclid'],
                'click_keys' => ['ttclid'],
                'pixel_keys' => ['tiktokPixelId'],
                'events' => [
                    'firstOpen' => 'firstOpen',
                    'registerSubmit' => 'registerSubmit',
                    'register' => 'CompleteRegistration',
                    'depositSubmit' => 'InitiateCheckout',
                    'firstDepositArrival' => 'firstDepositArrival',
                    'startTrial' => 'Subscribe',
                    'deposit' => 'CompletePayment',
                    'redeposit' => 'redeposit',
                    'withdraw' => 'withdraw',
                ],
            ],
            'kwai' => [
                'label' => 'Kwai',
                'config_tab' => 'kwai',
                'parameter_keys' => ['kwai_pixel_id', 'kwaiPixelBaseCode'],
                'click_keys' => [],
                'pixel_keys' => ['kwai_pixel_id', 'kwaiPixelBaseCode'],
                'events' => [
                    'register' => 'completeRegistration',
                    'depositSubmit' => 'initiatedCheckout',
                    'firstDepositArrival' => 'firstDeposit',
                    'deposit' => 'purchase',
                ],
            ],
            'google' => [
                'label' => 'Google GA4',
                'config_tab' => 'pixel',
                'parameter_keys' => ['gtagId', 'gclid'],
                'click_keys' => ['gclid'],
                'pixel_keys' => ['gtagId'],
                'events' => [
                    'firstOpen' => 'firstOpen',
                    'registerSubmit' => 'registerSubmit',
                    'register' => 'register',
                    'depositSubmit' => 'depositSubmit',
                    'firstDepositArrival' => 'firstDepositArrival',
                    'startTrial' => 'startTrial',
                    'deposit' => 'deposit',
                    'redeposit' => 'redeposit',
                    'withdraw' => 'withdraw',
                ],
            ],
            'gtm' => [
                'label' => 'Google Tag Manager',
                'config_tab' => 'pixel',
                'parameter_keys' => ['gtmId'],
                'click_keys' => [],
                'pixel_keys' => ['gtmId'],
                'browser_only' => true,
                'events' => [
                    'firstOpen' => 'firstOpen',
                    'registerSubmit' => 'registerSubmit',
                    'register' => 'register',
                    'depositSubmit' => 'depositSubmit',
                    'firstDepositArrival' => 'firstDepositArrival',
                    'startTrial' => 'startTrial',
                    'deposit' => 'deposit',
                    'redeposit' => 'redeposit',
                    'withdraw' => 'withdraw',
                ],
            ],
            'appsflyer' => [
                'label' => 'AppsFlyer',
                'config_tab' => 'appsflyer',
                'parameter_keys' => ['af_app_id', 'appsflyer_id', 'advertising_id', 'oaid', 'idfa', 'idfv'],
                'click_keys' => ['appsflyer_id'],
                'events' => [
                    'firstOpen' => 'firstOpen',
                    'registerSubmit' => 'registerSubmit',
                    'register' => 'register',
                    'depositSubmit' => 'depositSubmit',
                    'firstDepositArrival' => 'firstDepositArrival',
                    'startTrial' => 'startTrial',
                    'deposit' => 'deposit',
                    'redeposit' => 'redeposit',
                    'withdraw' => 'withdraw',
                ],
            ],
            'bigo' => [
                'label' => 'Bigo',
                'config_tab' => 'pixel',
                'parameter_keys' => ['bigoPixelId'],
                'click_keys' => [],
                'pixel_keys' => ['bigoPixelId'],
                'events' => [
                    'register' => 'ec_register',
                    'firstDepositArrival' => 'ec_purchase',
                ],
            ],
            'okspin' => [
                'label' => 'OKSpin',
                'config_tab' => 'pixel',
                'parameter_keys' => ['pixel_click_id', 'oks_pixel_id'],
                'click_keys' => ['pixel_click_id'],
                'pixel_keys' => ['oks_pixel_id'],
                'events' => [
                    'register' => 'EVENT_COMPLETE_REGISTRATION',
                    'firstDepositArrival' => 'EVENT_FIRST_DEPOSIT',
                    'deposit' => 'EVENT_PURCHASE',
                ],
            ],
            'voluum' => [
                'label' => 'Voluum',
                'config_tab' => 'voluum',
                'parameter_keys' => ['cid'],
                'click_keys' => ['cid'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit', 'redeposit' => 'redeposit'],
            ],
            'traffic_factory' => [
                'label' => 'Traffic Factory',
                'config_tab' => 'traffic-factory',
                'parameter_keys' => ['tfTracker'],
                'click_keys' => ['tfTracker'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit', 'redeposit' => 'redeposit'],
            ],
            'propellerads' => [
                'label' => 'PropellerAds',
                'config_tab' => 'propellerads',
                'parameter_keys' => ['visitor_id'],
                'click_keys' => ['visitor_id'],
                'events' => ['register' => 'register'],
            ],
            'red_track' => [
                'label' => 'Red Track',
                'config_tab' => 'red-track',
                'parameter_keys' => ['rtCid'],
                'click_keys' => ['rtCid'],
                'events' => ['register' => 'register', 'depositSubmit' => 'depositSubmit', 'startTrial' => 'startTrial', 'deposit' => 'deposit', 'redeposit' => 'redeposit'],
            ],
            'outbrain' => [
                'label' => 'Outbrain',
                'config_tab' => 'postback',
                'parameter_keys' => ['obclid'],
                'click_keys' => ['obclid'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit', 'redeposit' => 'redeposit'],
            ],
            'kadam' => [
                'label' => 'Kadam',
                'config_tab' => 'postback',
                'parameter_keys' => ['kadam_id'],
                'click_keys' => ['kadam_id'],
                'events' => ['register' => 'register'],
            ],
            'phoenix_ads' => [
                'label' => 'Phoenix Ads',
                'config_tab' => 'postback',
                'parameter_keys' => ['phxCid'],
                'click_keys' => ['phxCid'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial'],
            ],
            'mgskyads' => [
                'label' => 'MgSkyAds',
                'config_tab' => 'postback',
                'parameter_keys' => ['mgsClickId'],
                'click_keys' => ['mgsClickId'],
                'events' => ['register' => 'EVENT_COMPLETE_REGISTRATION', 'firstDepositArrival' => 'EVENT_FIRST_DEPOSIT', 'deposit' => 'EVENT_PURCHASE'],
            ],
            'devils_tracker' => [
                'label' => 'Devils tracker',
                'config_tab' => 'postback',
                'parameter_keys' => ['devilsClickId'],
                'click_keys' => ['devilsClickId'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial'],
            ],
            'macan_studio' => [
                'label' => 'Macan Studio',
                'config_tab' => 'postback',
                'parameter_keys' => ['macanClickId'],
                'click_keys' => ['macanClickId'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit', 'redeposit' => 'redeposit'],
            ],
            'routerhub' => [
                'label' => 'RouterHub',
                'config_tab' => 'postback',
                'parameter_keys' => ['rbclickid'],
                'click_keys' => ['rbclickid'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit'],
            ],
            'egw' => [
                'label' => 'EGW / Transsion',
                'config_tab' => 'postback',
                'parameter_keys' => ['egwId'],
                'click_keys' => [],
                'pixel_keys' => ['egwId'],
                'events' => ['register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit'],
            ],
            'fortune' => [
                'label' => 'Fortune',
                'config_tab' => 'postback',
                'parameter_keys' => ['fortune', 'clickId'],
                'click_keys' => ['clickId'],
                'events' => ['register' => 'register', 'depositSubmit' => 'viewContent', 'startTrial' => 'startTrial', 'deposit' => 'deposit'],
            ],
            'keitaro' => [
                'label' => 'Keitaro',
                'config_tab' => 'keitaro',
                'parameter_keys' => ['keitaroClickId'],
                'click_keys' => ['keitaroClickId'],
                'events' => ['register' => 'register', 'depositSubmit' => 'depositSubmit', 'startTrial' => 'startTrial', 'deposit' => 'deposit'],
            ],
            'revosurge' => [
                'label' => 'Revosurge',
                'config_tab' => 'postback',
                'parameter_keys' => ['clickid', 'revosurge'],
                'click_keys' => ['clickid'],
                'events' => ['login' => 'login', 'register' => 'register', 'startTrial' => 'startTrial'],
            ],
            'resiliencemedia' => [
                'label' => 'Resiliencemedia',
                'config_tab' => 'resiliencemedia',
                'parameter_keys' => ['rmClickId'],
                'click_keys' => ['rmClickId'],
                'events' => ['login' => 'login', 'register' => 'register', 'startTrial' => 'startTrial', 'deposit' => 'deposit', 'withdraw' => 'withdraw', 'redeposit' => 'redeposit'],
            ],
            'snapchat' => [
                'label' => 'Snapchat',
                'config_tab' => 'snapchat',
                'parameter_keys' => [],
                'click_keys' => [],
                'events' => ['register' => 'SIGN_UP', 'depositSubmit' => 'START_CHECKOUT', 'deposit' => 'PURCHASE'],
            ],
        ];
    }

    public static function platformForTracking(array $tracking): array
    {
        $matched = [];
        foreach (self::platforms() as $platform => $definition) {
            foreach ($definition['parameter_keys'] ?? [] as $key) {
                if (isset($tracking[$key]) && trim((string) $tracking[$key]) !== '') {
                    $matched[] = $platform;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    public static function clickIds(array $tracking): array
    {
        $ids = [];
        foreach (self::CLICK_ID_KEYS as $key) {
            if (isset($tracking[$key]) && trim((string) $tracking[$key]) !== '') {
                $ids[$key] = trim((string) $tracking[$key]);
            }
        }

        return $ids;
    }

    public static function clickIdForPlatform(string $platform, array $tracking)
    {
        $definition = self::platforms()[$platform] ?? [];
        foreach ($definition['click_keys'] ?? [] as $key) {
            if (isset($tracking[$key]) && trim((string) $tracking[$key]) !== '') {
                return trim((string) $tracking[$key]);
            }
        }

        return null;
    }

    public static function pixelIdForPlatform(string $platform, array $tracking)
    {
        $definition = self::platforms()[$platform] ?? [];
        foreach ($definition['pixel_keys'] ?? [] as $key) {
            if (isset($tracking[$key]) && trim((string) $tracking[$key]) !== '') {
                return trim((string) $tracking[$key]);
            }
        }

        return null;
    }

    public static function eventName(string $platform, string $event)
    {
        $definition = self::platforms()[$platform] ?? [];
        $events = $definition['events'] ?? [];

        return $events[$event] ?? null;
    }

    public static function standardEvent(string $event): string
    {
        if ($event === 'firstDepositArrival' || $event === 'startTrial') {
            return 'first_deposit';
        }

        if ($event === 'redeposit') {
            return 'deposit';
        }

        return $event;
    }
}
