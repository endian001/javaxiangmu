<?php
namespace App\Services;

class Lib
{
    /**
     * 获取ip地址
     */
    public static function getIpAddress($ip)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, sprintf('https://67ip.cn/check?ip=%s&token=%s', $ip, '53319c68fdda40a8b905d032bac04f45'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // 设置超时时间（1秒连接超时，2秒读取超时）
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $output = curl_exec($ch);
        // 如果请求失败，返回默认的空数据
        if(curl_errno($ch)) {
            curl_close($ch);
            return json_encode(['code' => 0, 'data' => ['country' => '', 'province' => '', 'city' => '']]);
        }
        curl_close($ch);
        return $output;
    }
}