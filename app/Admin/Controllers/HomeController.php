<?php

namespace App\Admin\Controllers;

use App\Admin\Metrics\Examples;
use App\Admin\Metrics\Home;
use App\Http\Controllers\Controller;
use App\Models\ActivityApply;
use App\Models\AgentApply;
use App\Models\Recharge;
use App\Models\SystemConfig;
use App\Models\Withdraw;
use Dcat\Admin\Http\Controllers\Dashboard;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Card;
use App\Services\TgService;
class HomeController extends Controller
{
    public function index(Content $content)
    {

        return $content
            ->header('首页')
            ->description('')
            ->body(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $column->row(function (Row $row) {
                        $row->column(3, new Home\TodayRecharge());
                        $row->column(3, new Home\TodayWithdraw());
                        $row->column(3, new Home\TotalUsers());
                        $row->column(3, new Home\AgentPercent());
                    });
                    $column->row(function (Row $row) {
                        $row->column(3, new Home\MonthRecharge());
                        $row->column(3, new Home\MonthWithdraw());
                        //$row->column(3, new Home\Yktotal());
                    });
                });
                // $row->column(12, function (Column $column) {
                //     $column->row(function (Row $row) {
                //         $row->column(3, new Home\TodayRecharge());
                //         $row->column(3, new Home\TodayWithdraw());
                //         $row->column(3, new Home\TotalUsers());
                //         $row->column(3, new Home\AgentPercent());
                //     });
                // });

                // 一行多列
                /*$row->column(12, function (Column $column) {
                    $column->row(function (Row $row) {
                        $merchantCredit = $this->merchantCredit();


            $tg = New TgService;
            $gamelist =$tg->gamesalllist();        
            $platgamename['universal'] = '通用额度';
            foreach ($gamelist as $val){
               $platgamename[$val['platform_code']]  = $val['platformname'];
            }   
            $platgame = $tg->engamelist();

                        if($merchantCredit['data']['money_type']==2){
                            $row->column(12, $this->card('通用额度' , (string)$merchantCredit['data']['merchant_money']['universal_money'], '#20c9b1'));
                        }
                        if($merchantCredit['data']['money_type']==1){
                        foreach ($merchantCredit['data']['merchant_money'] as $key=>$val) {
                            $vote = str_replace('_money','',$key);
                            if(in_array($vote,$platgame)) {
                                $row->column(2, $this->card($platgamename[$vote] , (string)$val, '#7986CB'));
                            }
                        }
                        }
                    });
                });*/

                $row->column(12, function (Column $column) {
                    $column->row(function (Row $row) {
                        $row->column(6,Card::make('近7天充值',new Home\Chart\Recharge()));
                        $row->column(6,Card::make('近7天提现',new Home\Chart\Withdraw()));
                    });
                });
            });
    }

    protected function card($text,$money, $color = '#fff')
    {
        $text = $this->p( $text,$money);
        return <<<EOF
<div style="background:$color;padding:10px 22px 16px;box-shadow:0 1px 3px 1px rgba(34, 25, 25, 0.1);margin-bottom:8px;">
   <li>$text</li>
</div>
EOF;
    }

    protected function p($text,$money, $height = 40)
    {
        return "<p style='height:{$height}px;color:#fff;text-align: center;padding-top: 10px; font-size:16px;'><span style='width: 100%; float: left;'>$text</span><span style='width: 100%;float: left;'>$money</span></p>";
    }

    private function generateCode(Array $data)
    {
        ksort($data);
        $str = '';
        foreach($data as $v){
            $str .= $v;
        }
        $str .= $this->sign_key;
        return strtoupper(md5($str));
    }

    private function sendRequest($url,$post_data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $contents = curl_exec($ch);
        curl_close($ch);

        return json_decode ($contents, TRUE);
    }

    /**
     * 商户额度查询
     *
     * @return void
     */
    public  function merchantCredit()
    {
        $this->api_url = SystemConfig::getValue('game_api');
        $this->api_account = SystemConfig::getValue('merchant_account');
        $this->sign_key = SystemConfig::getValue('api_secret');
        $data = [
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/user/all-credit",$data);
        return $res;
    }

    public function getAlertData()
    {
		$date = date('Y-m-d H:i:s',time() - 600);
        $recharge_apply = Recharge::where('state',1)->where('created_at','>',$date)->count();
        $withdraw_apply = Withdraw::where('state',1)->where('created_at','>',$date)->count();
        $agent_apply = AgentApply::where('state',1)->where('created_at','>',$date)->count();
        $activity_apply = ActivityApply::where('state',1)->where('created_at','>',$date)->count();
        $alert_type = SystemConfig::getValue('notice_set');
        $auto_refresh = SystemConfig::getValue('auto_refresh') ?? 0;
        $auto_refresh_interval = SystemConfig::getValue('auto_refresh_interval') ?? 5;

		$recharge_apply_audio = env('APP_URL').'/uploads/'.SystemConfig::getValue('recharge_apply_audio');
		$withdraw_apply_audio = env('APP_URL').'/uploads/'.SystemConfig::getValue('withdraw_apply_audio');
		$activity_apply_audio = env('APP_URL').'/uploads/'.SystemConfig::getValue('activity_apply_audio');
		$agent_apply_audio = env('APP_URL').'/uploads/'.SystemConfig::getValue('agent_apply_audio');
        
        return ['recharge_apply' => $recharge_apply,'withdraw_apply' => $withdraw_apply,'agent_apply' => $agent_apply,'activity_apply' => $activity_apply,
            'alert_type' => $alert_type,'recharge_apply_audio' => $recharge_apply_audio ?? '','withdraw_apply_audio' => $withdraw_apply_audio ?? '',
            'activity_apply_audio' => $activity_apply_audio ?? '','agent_apply_audio' => $agent_apply_audio ?? '','auto_refresh_interval' => $auto_refresh_interval,'auto_refresh' => $auto_refresh];
    }
}
