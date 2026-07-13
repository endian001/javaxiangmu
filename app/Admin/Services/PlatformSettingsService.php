<?php

namespace App\Admin\Services;

use InvalidArgumentException;

class PlatformSettingsService
{
    private const TABS = [
        'platform' => '平台配置',
        'download-links' => '下载链接',
        'user-info' => '用户信息',
        'frontend-style' => '前台显示样式',
        'app-package' => 'APP 打包',
        'app-download' => 'APP 下载设置',
        'wxgame' => 'WXGAME',
    ];

    private const FIELDS = [
        'platform' => [
            ['key' => 'platform_maintenance', 'label' => '平台维护', 'type' => 'switch'],
            ['key' => 'platform_maintenance_end_at', 'label' => '维护结束时间', 'type' => 'datetime'],
            ['key' => 'platform_main_domain', 'label' => '平台主域名', 'type' => 'url'],
            ['key' => 'platform_lottery_trial', 'label' => '彩票试玩', 'type' => 'switch'],
            ['key' => 'platform_unique_ip', 'label' => '用户使用唯一 IP', 'type' => 'switch'],
            ['key' => 'platform_app_launch_game', 'label' => 'APP 内启动游戏', 'type' => 'switch'],
            ['key' => 'platform_customer_plugin', 'label' => '客服插件', 'type' => 'text'],
        ],
        'download-links' => [
            ['key' => 'platform_mobile_web_url', 'label' => '手机网页', 'type' => 'url'],
            ['key' => 'platform_h5_download_icon', 'label' => 'H5App 下载列图标', 'type' => 'image'],
            ['key' => 'platform_facebook_url', 'label' => 'FACEBOOK', 'type' => 'url'],
            ['key' => 'platform_telegram_url', 'label' => 'TELEGRAM', 'type' => 'url'],
            ['key' => 'platform_whatsapp_url', 'label' => 'WHATSAPP', 'type' => 'url'],
            ['key' => 'platform_instagram_url', 'label' => 'INSTAGRAM', 'type' => 'url'],
            ['key' => 'platform_livechat_url', 'label' => 'Livechat', 'type' => 'url'],
            ['key' => 'platform_telegram_bot_url', 'label' => 'TELEGRAMBOT', 'type' => 'url'],
        ],
        'user-info' => [
            ['key' => 'platform_user_info_enabled', 'label' => '开启用户信息', 'type' => 'switch'],
            ['key' => 'platform_user_info_editable', 'label' => '允许修改用户信息', 'type' => 'switch'],
            ['key' => 'platform_registration_fields', 'label' => '开启注册栏位', 'type' => 'textarea'],
            ['key' => 'platform_required_registration_fields', 'label' => '必填注册栏位', 'type' => 'textarea'],
            ['key' => 'platform_username_format', 'label' => '用户名格式', 'type' => 'text'],
            ['key' => 'platform_username_min_length', 'label' => '用户名最小长度', 'type' => 'number'],
            ['key' => 'platform_username_max_length', 'label' => '用户名最大长度', 'type' => 'number'],
            ['key' => 'platform_phone_format', 'label' => '电话号码格式', 'type' => 'text'],
            ['key' => 'platform_phone_min_length', 'label' => '电话号码最小长度', 'type' => 'number'],
            ['key' => 'platform_phone_max_length', 'label' => '电话号码最大长度', 'type' => 'number'],
        ],
        'frontend-style' => [
            ['key' => 'platform_mobile_logo', 'label' => '手机端 Logo', 'type' => 'image'],
            ['key' => 'platform_web_logo', 'label' => '网页端 Logo', 'type' => 'image'],
            ['key' => 'platform_favicon', 'label' => '网站图示 Fav icon', 'type' => 'image'],
            ['key' => 'platform_mobile_splash', 'label' => '手机端启动画面', 'type' => 'image'],
            ['key' => 'platform_desktop_app_title', 'label' => '桌面 APP 标题', 'type' => 'text'],
            ['key' => 'platform_desktop_background_color', 'label' => '桌面 APP 背景色', 'type' => 'color'],
            ['key' => 'platform_desktop_logo', 'label' => '桌面 APP Logo', 'type' => 'image'],
        ],
        'app-package' => [
            ['key' => 'platform_app_desktop_name', 'label' => 'APP 桌面名字', 'type' => 'text'],
            ['key' => 'platform_app_stage_one_background', 'label' => '第一阶段启动页背景图', 'type' => 'image'],
            ['key' => 'platform_ios_title_description', 'label' => '苹果标题描述', 'type' => 'text'],
            ['key' => 'platform_ios_unsigned_domain', 'label' => '苹果免签 APP 域名', 'type' => 'url'],
            ['key' => 'platform_app_icon', 'label' => 'APP Icon 512x512', 'type' => 'image'],
            ['key' => 'platform_app_splash', 'label' => '启动页 1080x1920', 'type' => 'image'],
            ['key' => 'platform_push_icon', 'label' => '推送通知 Icon 48x48', 'type' => 'image'],
            ['key' => 'platform_package_suffix', 'label' => 'APP 包名后缀', 'type' => 'text'],
            ['key' => 'platform_google_service_json', 'label' => 'google_service.json', 'type' => 'file'],
            ['key' => 'platform_google_web_client_id', 'label' => 'google_web_client_id', 'type' => 'text'],
            ['key' => 'platform_facebook_app_id', 'label' => 'facebook_app_id', 'type' => 'text'],
            ['key' => 'platform_facebook_client_token', 'label' => 'facebook_client_token', 'type' => 'text'],
            ['key' => 'platform_appsflyer_dev_key', 'label' => 'Appsflyer DEV Key', 'type' => 'text'],
            ['key' => 'platform_adjust_app_token', 'label' => 'Adjust APP Token', 'type' => 'text'],
            ['key' => 'platform_package_agent_code', 'label' => '代理账号 / 推荐码', 'type' => 'text'],
            ['key' => 'platform_android_fixed_domain', 'label' => '安卓 APP 固定域名', 'type' => 'url'],
            ['key' => 'platform_snowball_app_id', 'label' => 'Snow Ball App ID', 'type' => 'text'],
        ],
        'app-download' => [
            ['key' => 'platform_android_app_url', 'label' => '安卓 APP', 'type' => 'url'],
            ['key' => 'platform_app_version', 'label' => 'APP 版本', 'type' => 'text'],
            ['key' => 'platform_ios_unsigned_url', 'label' => '苹果免签版 APP', 'type' => 'url'],
            ['key' => 'platform_download_bar_logo', 'label' => '顶部 APP 标志', 'type' => 'image'],
            ['key' => 'platform_download_text_color', 'label' => '下载列描述文字颜色', 'type' => 'color'],
            ['key' => 'platform_download_background_color', 'label' => '下载列背景色', 'type' => 'color'],
            ['key' => 'platform_download_top_text', 'label' => '顶部 APP 描述文字', 'type' => 'text'],
            ['key' => 'platform_download_bottom_text', 'label' => '底部下载提示', 'type' => 'textarea'],
            ['key' => 'platform_login_download_prompt', 'label' => '登入提示文案', 'type' => 'textarea'],
            ['key' => 'platform_download_prompt_enabled', 'label' => '提示下载开关', 'type' => 'switch'],
            ['key' => 'platform_download_redirect_mode', 'label' => 'APP 下载跳转选项', 'type' => 'text'],
            ['key' => 'platform_pwa_store_url', 'label' => 'PWA 商店下载链接', 'type' => 'url'],
            ['key' => 'platform_apk_store_url', 'label' => 'APK 商店下载链接', 'type' => 'url'],
            ['key' => 'platform_force_download_persistent', 'label' => '强制下载持久化', 'type' => 'switch'],
            ['key' => 'platform_continue_browser_enabled', 'label' => '显示继续使用浏览器', 'type' => 'switch'],
            ['key' => 'platform_scenario_download_rules', 'label' => '首充 / 提现 / 绑卡下载规则', 'type' => 'textarea'],
        ],
        'wxgame' => [
            ['key' => 'wxgame_enabled', 'label' => '启用 WXGAME', 'type' => 'switch'],
            ['key' => 'wxgame_api_domain', 'label' => 'API 域名', 'type' => 'url'],
            ['key' => 'wxgame_access_key_id', 'label' => 'AccessKeyId', 'type' => 'text'],
            ['key' => 'wxgame_access_key_secret', 'label' => 'AccessKeySecret', 'type' => 'text'],
            ['key' => 'wxgame_app_id', 'label' => 'App ID', 'type' => 'text'],
            ['key' => 'wxgame_callback_domain', 'label' => '回调地址前缀', 'type' => 'url'],
            ['key' => 'wxgame_currency', 'label' => '币种', 'type' => 'text'],
            ['key' => 'wxgame_token_secret', 'label' => '玩家 Token 密钥', 'type' => 'text'],
            ['key' => 'wxgame_callback_signature_required', 'label' => '回调签名校验', 'type' => 'switch'],
            ['key' => 'wxgame_callback_sign_window', 'label' => '签名时间窗口秒数', 'type' => 'number'],
            ['key' => 'wxgame_ssl_verify', 'label' => '请求 SSL 校验', 'type' => 'switch'],
        ],
    ];

    public function tabs(): array
    {
        return self::TABS;
    }

    public function fields($tab): array
    {
        if (!isset(self::FIELDS[$tab])) {
            throw new InvalidArgumentException('平台配置标签不存在');
        }

        return self::FIELDS[$tab];
    }

    public function filterValues($tab, array $values): array
    {
        $allowed = [];
        foreach ($this->fields($tab) as $field) {
            $allowed[$field['key']] = $field['type'];
        }

        $filtered = [];
        foreach ($values as $key => $value) {
            if (!isset($allowed[$key]) || is_array($value) || is_object($value)) {
                continue;
            }
            if ($allowed[$key] === 'switch') {
                $filtered[$key] = in_array(
                    $value,
                    [1, '1', true, 'true', 'on', 'yes'],
                    true
                ) ? '1' : '0';
                continue;
            }
            $filtered[$key] = mb_substr(trim(strip_tags((string) $value)), 0, 5000);
        }

        return $filtered;
    }

    public function fileFields($tab): array
    {
        $keys = [];
        foreach ($this->fields($tab) as $field) {
            if (in_array($field['type'], ['image', 'file'], true)) {
                $keys[] = $field['key'];
            }
        }

        return $keys;
    }
}
