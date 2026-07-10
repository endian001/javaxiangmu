<?php

namespace App\Services;

class Zgpay
{

    public function __construct($merchant_id,$api_secret)
    {
        $this->merchant_id = $merchant_id;
        $this->api_secret = $api_secret;
    }

    public function withdraw($order_no,$amount,$username,$wallet_address)
    {
        $url = 'https://zgpay.cc/api/withdraw';
        $ch = curl_init($url);

        $merchant_id = $this->merchant_id;
        //商户订单号
        $out_trade_no = $order_no;
        //提款金额
        $total_fee = $amount;
        // 用户账号
        $user_name = $username;
        // 用户钱包地址
        $user_wallet = $wallet_address;
        //成功支付回调地址
        $notify_url = env('APP_URL').'/zgp-withdraw-callback';
        //商户密钥
        $api_secret = $this->api_secret;

        $sign = "";
        if ($merchant_id != '') {
            $sign .= $merchant_id;
        }
        if ($notify_url != '') {
            $sign .= $notify_url;
        }
        if ($out_trade_no != '') {
            $sign .= $out_trade_no;
        }
        if ($total_fee != '') {
            $sign .= $total_fee;
        }
        if ($user_name != '') {
            $sign .= $user_name;
        }
        if ($user_wallet != '') {
            $sign .= $user_wallet;
        }
        $sign = md5($sign . $api_secret);

        $data = array(
            'merchant_id' => $merchant_id,
            'notify_url' => $notify_url,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $total_fee,
            'user_name' => $user_name,
            'user_wallet' => $user_wallet,
            'sign' => $sign,
        );
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //Execute the request
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
