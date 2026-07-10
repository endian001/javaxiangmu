<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\SystemConfig;
use Validator;
use Illuminate\Support\Facades\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $engamelist = [];
    

   
    public function returnMsg($code, $data = [],$message = '')
    {
        $lang = Request::header('Lang') ?? 'zh';
        $lang = strtolower(trim((string) $lang));
        $messages = config('errorcode.code') ?: [];
        $fallbackLang = isset($messages['zh']) ? 'zh' : (array_key_first($messages) ?: '');
        $defaultMessage = $messages[$lang][(int) $code] ?? ($messages[$fallbackLang][(int) $code] ?? '');
        
        return response()->json([
            'code'    => $code,
            'message' => $message ?: $defaultMessage,
            'data'    => $data,
        ]);
    }

    protected function customerServicePayload()
    {
        $url = $this->customerServiceLink();
        $streamChat = $this->customerStreamChatStatus();
        $streamReady = $streamChat['enabled'] && $streamChat['configured'];
        $appUrl = rtrim((string) env('APP_URL'), '/');
        $serviceType = SystemConfig::getValue('service_type') ?: 'link';
        $workOrderEnabled = $serviceType === 'gongdan';
        $workOrderPageUrl = $workOrderEnabled ? $appUrl . '/support/work-orders.html' : '';
        $serviceUrl = $url ?: $workOrderPageUrl;

        return [
            'url' => $serviceUrl,
            'kf_url' => $serviceUrl,
            'service_url' => $serviceUrl,
            'service_link' => $serviceUrl,
            'domain' => $appUrl,
            'service_type' => $serviceType,
            'configured' => $url !== '' || $streamReady || $workOrderEnabled,
            'link_configured' => $url !== '',
            'work_order_enabled' => $workOrderEnabled,
            'work_order_page_url' => $workOrderPageUrl,
            'work_order_list_url' => $appUrl . '/api/work-orders',
            'work_order_create_url' => $appUrl . '/api/work-orders/create',
            'work_order_detail_url' => $appUrl . '/api/work-orders/{id}',
            'work_order_reply_url' => $appUrl . '/api/work-orders/{id}/reply',
            'work_order_close_url' => $appUrl . '/api/work-orders/{id}/close',
            'ws_enabled' => $streamReady,
            'stream_chat' => $streamChat,
            'stream_config_url' => $appUrl . '/api/stream/config',
            'stream_token_url' => $appUrl . '/api/stream/token',
            'stream_channel_url' => $appUrl . '/api/stream/channel',
        ];
    }

    protected function customerServiceLink()
    {
        $candidates = [
            SystemConfig::getValue('kf_url'),
            SystemConfig::getValue('service_url'),
            SystemConfig::getValue('customer_service_url'),
            SystemConfig::getValue('online_service_url'),
            env('KF_URL'),
            env('SERVICE_URL'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($this->isUsableCustomerServiceUrl($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    protected function isUsableCustomerServiceUrl($url)
    {
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        $placeholderHosts = [
            'baidu.com',
            'example.com',
            'example.net',
            'example.org',
            'localhost',
            '127.0.0.1',
        ];

        if (in_array($host, $placeholderHosts, true)) {
            return false;
        }

        return substr($host, -10) !== '.baidu.com';
    }

    protected function customerStreamChatStatus()
    {
        $enabled = (int) SystemConfig::getValue('stream_chat_enabled') === 1;
        $apiKey = trim((string) SystemConfig::getValue('stream_chat_api_key'));
        $secret = trim((string) SystemConfig::getValue('stream_chat_secret'));
        $messageLimit = SystemConfig::getValue('stream_chat_message_limit');
        if (!is_numeric($messageLimit)) {
            $messageLimit = 50;
        }
        $messageLimit = max(10, min(500, (int) $messageLimit));

        return [
            'enabled' => $enabled,
            'configured' => $apiKey !== '' && $secret !== '',
            'api_key' => $apiKey,
            'has_secret' => $secret !== '',
            'message_limit' => $messageLimit,
        ];
    }

    protected function firstUsablePublicUrl($value)
    {
        $items = array_values(array_filter(array_map('trim', explode(',', (string) $value))));

        foreach ($items as $item) {
            if ($this->isUsablePublicUrl($item)) {
                return rtrim($item, '/');
            }
        }

        return '';
    }

    protected function firstUsablePublicUrlFrom(array $values)
    {
        foreach ($values as $value) {
            $url = $this->firstUsablePublicUrl($value);
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    protected function isUsablePublicUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        $placeholderHosts = [
            'baidu.com',
            'example.com',
            'example.net',
            'example.org',
            'localhost',
        ];

        if (in_array($host, $placeholderHosts, true)) {
            return false;
        }

        if (preg_match('/^(127|10|0)\./', $host) || preg_match('/^192\.168\./', $host)) {
            return false;
        }

        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host)) {
            return false;
        }

        return substr($host, -10) !== '.baidu.com';
    }

    protected function appPublicUrl()
    {
        return $this->firstUsablePublicUrlFrom([
            env('APP_URL'),
            env('WAP_URL'),
            env('PC_URL'),
            url('/'),
        ]);
    }

    protected function agentPublicUrl()
    {
        return $this->firstUsablePublicUrlFrom([
            SystemConfig::getValue('agent_url'),
            env('AGENT_URL'),
            env('AGENT_LOGIN'),
            env('APP_URL'),
            env('WAP_URL'),
            env('PC_URL'),
            url('/'),
        ]);
    }

    protected function agentLoginPublicUrl()
    {
        return $this->firstUsablePublicUrlFrom([
            env('AGENT_LOGIN'),
            SystemConfig::getValue('agent_url'),
            env('AGENT_URL'),
            env('APP_URL'),
            url('/'),
        ]);
    }

    protected function invitePublicUrl($prefix, $userId, array $query = [])
    {
        $baseUrl = $this->firstUsablePublicUrlFrom([
            $prefix,
            env('WAP_URL'),
            env('PC_URL'),
            env('APP_URL'),
            url('/'),
        ]);

        $params = array_merge(['pid' => (int) $userId], $query);

        return $baseUrl . '/#/register?' . http_build_query($params);
    }

    public function validate($request, $rules, $message){
        $Validator = Validator::make($request->all(),$rules,$message);
        if($Validator->fails()){
            $result = [];
            foreach(json_decode(json_encode($Validator->errors()),true) as $k => $v){
                $result['code'] = 1000;
                $result['message'] = $v[0];
				header('Content-type:text/json');
                echo json_encode($result);
                exit;
            }
        }
    }
    public function isMobile()
    {
            if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
                return true;
            } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
                return true;
            } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])){
                return true;
            } elseif (isset($_SERVER['HTTP_USER_AGENT']) &&  preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
                return true;
            }
            return false;
    }
    public function write_log($data,$filepath=''){
        $data = is_array($data) ? json_encode($data) : $data;
        $data = date('Y-m-d H:i:s') . '   ' . $data;

        $filepath = $filepath ? $filepath : './pay_log.txt';
        if($rsp = fopen($filepath, "a+b")) {
            fwrite($rsp, $data);
            fwrite($rsp, PHP_EOL."--------------------".PHP_EOL);
            fclose($rsp);
        }
    }	
}
