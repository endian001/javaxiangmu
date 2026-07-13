<?php

namespace App\Services;

use App\Models\PayType;

class PayService
{

    public function __construct()
    {
        $this->payconfig = config('pay');
    }

    public function cgpay($bill_no, $money, $channel = null)
    {
        $merchant = $this->cgpayMerchantConfig($channel);
        $params = [
            'MerchantId'=> $merchant['MerchantId'],
            'MerchantOrderId' => $bill_no,
            'Amount' => $money * 100000000,//增加一定随机数金额
            'OrderTimeLive' => '300',
            'OrderDescription'=> 'buy',
            "Symbol" => 'CGP',
            'CallBackUrl'=> env('ADMIN_DOMAIN').'/api/pay/cgpay_notify',
            'ReferUrl' => env('PC_URL')
        ];
        $md5key = $merchant['md5key'];
		$url = $merchant['payurl'];
        $params['Sign'] = $this->cgpay_sign($params,$md5key);
        $json = $this->curl_request($url,json_encode($params));
        return $json;
    }

    protected function cgpayMerchantConfig($channel = null)
    {
        $payType = $this->resolvePayType($channel);

        return [
            'MerchantId' => $this->filledValue($payType, 'merchant_no', $this->payconfig['cgpay']['MerchantId']),
            'md5key' => $this->filledValue($payType, 'merchant_key', $this->payconfig['cgpay']['md5key']),
            'payurl' => $this->filledValue($payType, 'merchant_url', $this->payconfig['cgpay']['payurl']),
        ];
    }

    protected function resolvePayType($channel = null)
    {
        if ($channel instanceof PayType) {
            return $channel;
        }

        if ($channel && method_exists($channel, 'payType')) {
            $payType = $channel->payType;
            if ($payType && (int)($payType->state ?? 1) === 1) {
                return $payType;
            }
        }

        return PayType::where('state', 1)
            ->where(function ($query) {
                $query->where('merchant_identifier', 'like', '%cgpay%')
                    ->orWhere('merchant_code', 'like', '%cgpay%')
                    ->orWhere('name', 'like', '%CGPay%')
                    ->orWhere('name', 'like', '%CGPAY%');
            })
            ->whereNotNull('merchant_no')
            ->whereNotNull('merchant_key')
            ->whereNotNull('merchant_url')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    protected function filledValue($model, $field, $fallback)
    {
        if (!$model) {
            return $fallback;
        }

        $value = trim((string)($model->{$field} ?? ''));
        return $value === '' ? $fallback : $value;
    }

	
    public function cgpay_sign($data,$md5key){
        $data = array_change_key_case($data, CASE_LOWER);
        ksort($data);
        $str = '';
        foreach ($data as $k=>$v){
            if($k !== 'sign' && strlen($v)) $str .= $v.',';
        }
        $str .= $md5key;
        $str = md5($str);
        return strtoupper($str);
    }
	
    public function curl_request($url, $data=null, $method='POST', $header = array("content-type: application/json"), $https=true, $timeout = 10){
        $method = strtoupper($method);
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//访问的URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        if($https){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//https请求 不验证证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//https请求 不验证HOST
        }
        if ($method != "GET") {
            if($method == 'POST'){
                curl_setopt($ch, CURLOPT_POST, true);//请求方式为post请求
            }
            if ($method == 'PUT' || strtoupper($method) == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method); //设置请求方式
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//请求数据
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        //curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息
        $result = curl_exec($ch);//执行请求
        curl_close($ch);//关闭curl，释放资源
        return $result;
    }	
}
