<?php

namespace App\Admin\Forms;

use Dcat\Admin\Widgets\Form;
use App\Models\SystemConfig;

class PaySetting extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        foreach ($input as $k => $v) {
            $arr = ['key' => $k,'value' => $v];
            // dd($arr);
            SystemConfig::updateOrCreate(['key' => $k],$arr);
        }
        return $this
				->response()
				->success('操作成功')
				->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->tab('ZGPAY配置', function () {
            $this->text('merchant_id','商户号')->required();
            $this->text('zgp_secret','Api Secret')->required();
        });
        
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        $merchant_id = SystemConfig::where('key','merchant_id')->value('value') ?? '';
        $zgp_secret = SystemConfig::where('key','zgp_secret')->value('value') ?? '';
        return [
            'merchant_id'  => $merchant_id,
            'zgp_secret' => $zgp_secret,
        ];
    }
}
