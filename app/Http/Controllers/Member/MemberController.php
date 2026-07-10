<?php
//decode by http://www.yunlu99.com/
namespace App\Http\Controllers\Member;
use App\Http\Controllers\Controller;
use App\Models\GameRecord;
use App\Models\Message;
use App\Models\Activity;
use App\Models\ActivityApply;
use App\Models\SystemConfig;
use App\Models\TransferLog;
use App\Models\Userredpacket;
use App\Services\GamereportService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Recharge;
use App\Models\Suggestion;
use App\Models\UserMessage;
use App\Models\Withdraw;
use App\Models\Users;
use App\Models\UserVip;
use App\Services\TgService;
use App\Models\Template;
use mysql_xdevapi\Exception;
use App\Models\Usersmoney;
use Illuminate\Support\Facades\Cookie;
use App\Models\GameList;
use App\Models\RedEnvelopes;
use App\Models\Article;
use App\Models\Articlescate;

class MemberController extends Controller
{
    protected $state = [1 => '待审核', 2 => '通过', 3 => '失败'];
    protected $game_list = [] ;
    
    
    
    protected $gamelist = [];
    protected $path;
    protected $showlang;
    public function __construct()
    {
        $lang = Cookie::get("userlang");
        $this->showlang = $lang;
        if($lang=="en"){
            $path = 'web.template.e_mb10';    
        }else{
            $path = Template::where('client_type',1)->where('state',2)->first();
            $path = $path ? 'web.template.'.$path->template_id : 'web';            
        }   
        try {
            $tg = New TgService;
            $this->gamelist = $tg->getallbetgamelist();
            $this->gamemoney = $tg->getallmoneygame();
            $this->game_list = $tg->getallgamename();
            $this->gamemoney_list = $tg->getallmoneygamelist();
            $this->engamelist = $tg->engamelist();
        } catch (\Exception $e) {
            $this->gamelist = [];
            $this->gamemoney = [];
            $this->game_list = [];
            $this->gamemoney_list = [];
            $this->engamelist = [];
        }
        $this->path = $path;
    }

    public function center()
    {
        $lang = $this->showlang;
        $user = Auth::user();
        $balancelist = Usersmoney::getUserBalance(Auth::id());
        $list = Message::where('type', 1)->paginate(20);
        foreach ($list as $k => $v) {
            $user_message = UserMessage::where('message_id', $v->id)->count();
            $list[$k]['is_read'] = $user_message ?? 0;
        }



        
        return view($this->path . '.member.center', compact('user','balancelist','list','lang'));
    }

    public function transfer()
    {
        $lang = $this->showlang;
        $user = Auth::user();
        $list = TransferLog::where('user_id', Auth::id())->where('transfer_type', 'in', '0,1')->orderBy('id', 'desc')->paginate(10);
        foreach ($list as $k => $v) {
            $list[$k]['type'] = (($v['transfer_type'] == 0) ? '转入游戏' : '游戏转出') . $v['api_type'];
            $list[$k]['state'] = ($v['state'] == 0) ? '失败' : '成功';
            $list[$k]['out_trade_no'] = $v['order_no'];
            $list[$k]['amount'] = $v['money'];
        }
        $gamelist =$this->gamelist;

        $Balancelist = Usersmoney::getUserBalance(Auth::id());

/*        $userinfo = \App\Models\User::where("id",Auth::id())->first();
        $uservip = UserVip::where('status',1)->where('recharge','<=',$userinfo->paysum)->where('flow','<=',$userinfo->totalgame)->orderBy('id','desc')->first();
        $userinfo->vip = $uservip->id;
        $userinfo->save();*/

        return view($this->path . '.member.transfer', compact('user','list','gamelist','Balancelist','lang'));
    }




    public function centernew()
    {
        $lang = $this->showlang;
        $user = Auth::user();
        return view($this->path . '.member.centernew', compact('user','lang'));
    }

    /**
     * 完善信息
     *
     * @param Request $request
     * @return void
     */
    public function fillData(Request $request)
    {
        
        $data = $request->all();
        $is_adult = $data['birthday'] ? $this->isAdult($data['birthday']) : 0;
        if ($is_adult == 0) return $this->returnMsg(204);
        $user = Auth::user();
        $user->update($data);
        return $this->returnMsg(200);
    }

    public function getUserBalance()
    {
        if (Auth::check()) {
            $balance = Users::where("id", Auth::user()->id)->value("balance");
            echo $balance;
        } else {
            echo "登录已过期，请重新登录";
        }
        
    }

    private function isAdult($time)
    {
        $time = strtotime($time);
        return (time() - $time > 18 * 365 * 86400) ? 1 : 0;
    }

    /**
     * 反馈意见
     *
     * @param Request $request
     * @return void
     */
    public function suggestion(Request $request)
    {

        $webcontent = SystemConfig::query()->find("kf_url");
        $content = $webcontent['value'];
        return redirect($content);
        /* if ($request->isMethod('post')) {
            $data = $request->all();
            $data['user_id'] = Auth::user()->id;
            $res = Suggestion::create($data);
            return $this->returnMsg($res ? 200 : 500);
        }
        return view($this->path . '.member.suggestion');*/
    }

    /**
     * vip页面
     *
     * @return void
     */
    public function vip()
    {
        return view($this->path . '.member.vip');
    }

    /**
     * 消息列表
     *
     * @return void
     */
    public function mail($type)
    {
        $lang = $this->showlang;
        $list = Message::where('type', $type)->paginate(20);
        foreach ($list as $k => $v) {
            $user_message = UserMessage::where('message_id', $v->id)->count();
            $list[$k]['is_read'] = $user_message ?? 0;
        }
        return view($this->path . '.member.mail', compact('list', 'type','lang'));
    }

    public function mailDetail($id)
    {
        $lang = $this->showlang;
        $user_message = UserMessage::where('message_id', $id)->where('user_id', Auth::id())->first();
        if (!$user_message) {
            $arr = [
                'user_id' => Auth::id(),
                'message_id' => $id,
            ];
            UserMessage::create($arr);
        }
        $item = Message::find($id);
        return view($this->path . '.member.mail_detail', compact('item','lang'));
    }

    public function game(Request $request)
    {
        $data = $request->all();
        $plat_name = $data['plat_name'];
        $game_type = $data['game_type'];
        $is_mobile = isset($data['is_mobile']) ? $data['is_mobile'] : 0;
        $game_code = $data['game_code'] ?? '';
        $tg = new TgService;
        $user = Auth::user();
        if ($game_type) {
            $game = GameList::where('platform_name',$plat_name);
            if (!is_numeric($game_type)) $game = $game->where('game_code',$game_type);
            $game = $game->first();
            // dd($game);
            if ($game && $game->site_state != 1) {
                $ret['message'] = "游戏暂未开放";
                return view($this->path . '.member.noentergame', compact('ret'));
            }
        }
      
        $res = $tg->login($user->username, $plat_name, $game_type, $is_mobile, $game_code);
        
        if ($res['code'] == 200) {
            if( Users::where("id", Auth::user()->id)->value("transferstatus")) {
                $plat_name = ($plat_name=='fgdz') ? 'fg' : $plat_name;
                $ret = $user->transToTgAccount($plat_name, $game_type);
            }
            if($res){
                 $ret = $res;
                return redirect()->away($res['data']);
            }else{
                 $ret = $res;
                return view($this->path . '.member.noentergame', compact('ret'));
            }
        } else {
            $ret = $res;
            return view($this->path . '.member.noentergame', compact('ret')); 
        }
    }

    public function gamelist()
    {
        $data['totalRechargeMoney'] = Recharge::where('user_id', Auth::id())->where('state',2)->sum('real_money');
        $data['totalWithdrawMoney'] = Withdraw::where('user_id', Auth::id())->where('state',2)->sum('amount');
        $data['totalTransferLogInMoney'] = abs(TransferLog::where('user_id', Auth::id())->where('transfer_type',1)->sum('real_money'));
        $data['totalTransferLogOutMoney'] = abs(TransferLog::where('user_id', Auth::id())->where('transfer_type',0)->sum('real_money'));
        $data['totalGameRecordBetMoney'] = GameRecord::where('user_id', Auth::id())->sum('bet_amount');
        $data['totalGameRecordValidMoney'] = GameRecord::where('user_id', Auth::id())->sum('valid_amount');
        $data['totalGameRecordNums'] = GameRecord::where('user_id', Auth::id())->count();
        $data['totalGameRecordWinLoss'] = GameRecord::where('user_id', Auth::id())->sum('win_loss');
        $data['totalFanshuiNum'] = TransferLog::where('user_id', Auth::id())->where('transfer_type', 6)->count();
        $data['totalFanshuiFinsh'] = TransferLog::where('user_id', Auth::id())->where('state', 1)->where('transfer_type', 6)->sum('real_money');
        $data['totalFanshuiNoFinsh'] = TransferLog::where('user_id', Auth::id())->where('state', 0)->where('transfer_type', 6)->sum('real_money');

        $data['totalRedpacketNum'] = Userredpacket::where('uid', Auth::id())->count();

        $data['totalRedpacketFinsh'] = Userredpacket::where('uid', Auth::id())->where('status', 1)->sum('redpacketmoney');

        $data['totalRedpacketNoFinsh'] = Userredpacket::where('uid', Auth::id())->where('status', 0)->sum('redpacketmoney');

        $data['gamelist'] = $this->gamelist;
        return $this->returnMsg(200, $data,'转账成功');
    }


    public function usertransfer(Request $request)
    {
        $data = $request->all();
        $plat_name = $data['pay_way'];
        $money = $data['amount'];
        $types = $data['types'];
        if (!in_array($plat_name, $this->engamelist)) {
            return $this->returnMsg(500, '', '请选择平台');
        }
        if (!in_array($types, ['togame', 'toaccount'])) {
            return $this->returnMsg(500, '', '请选择转账方式');
        }
        if (!is_numeric($money) || $money < 0) {
            return $this->returnMsg(500, '', '请输入转账金额');
        }
        $mon = explode('.', $money * 100);
        if (count($mon) > 1) {
            return $this->returnMsg(500, '', '转账金额输入格式不正确');
        }
        $user = Auth::user();
        if ($types == 'toaccount') { //游戏转账到余额
            $ret = $user->Accounttranso($plat_name, $money);
        } else { //余额转账到游戏
            $ret = $user->transToAccount($plat_name, $money);
        }

        if ($ret['code'] == 200) {
            return $this->returnMsg(200, '转账成功');
        } else {
            return $this->returnMsg(500, [], $ret['message']);
        }
    }

    /**
     * 交易记录
     *
     * @return void
     */
    public function transRecord(Request $request)
    {
        if ($request->isMethod('post')) {
        $data = $request->all();
        $type = $data['type'];
        $start = $end = '';
        $limit = $data['limit'] ?? 10;
        if (isset($data['time'])) {
            list($start, $end) = [$data['time'][0], $data['time'][1]];
        }
        switch ($type) {
            case '0': //存款
                $list = Recharge::where('user_id', Auth::id())
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->orderBy('id', 'desc')->paginate($limit);
                foreach ($list as $k => $v) {
                    $list[$k]['state'] = $this->state[$v->state];

                    $list[$k]['type'] = ($v->pay_way == 10) ? '充值赠送' : '充值';
                }
                return $this->returnMsg(200, $list);
                break;

            case 1://提现
                $list = Withdraw::where('user_id', Auth::id())
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->orderBy('id', 'desc')->paginate($limit);
                foreach ($list as $k => $v) {
                    $list[$k]['state'] = $this->state[$v->state];
                    $list[$k]['out_trade_no'] = $v->order_no;
                    $list[$k]['type'] = $v['type'] == 1 ?'银行卡':'USDT';

                }
                return $this->returnMsg(200, $list);
                break;
            case 2://转账
                    $api_type = $data['api_type'];
                    $api_type = ($api_type[0]!="all") ? $data['api_type'][1] : "" ;

                    $list = TransferLog::where('user_id', Auth::id())->whereIn('transfer_type', [0,1,3,7,8,9])
                        ->when($start, function ($query) use ($start) {
                            return $query->where('created_at', '>=', $start);
                        })->when($end, function ($query) use ($end) {
                            return $query->where('created_at', '<=', $end);
                        })->when($api_type, function ($query) use ($api_type) {
                            return $query->where('api_type', '=', $api_type);
                        })->orderBy('id', 'desc')->paginate($limit);
                    $gamelist =$this->gamemoney;
                    foreach ($list as $k => $v) {
                        if (in_array($v['transfer_type'],[0,1])) {
                            $apiName = isset($gamelist[$v['api_type']]) ? $gamelist[$v['api_type']] : $v['api_type'];
                            $list[$k]['type'] = (($v['transfer_type']==1) ? '转出' : '转入').$apiName;
                        } else {
                            $list[$k]['type'] = $v->remark;
                        }
                        $list[$k]['state'] = ($v['state']==0) ? '失败' : '成功';
                        $list[$k]['out_trade_no'] = $v['order_no'];
                        $list[$k]['amount'] = abs($v['money']);
                    }
                return $this->returnMsg(200, $list);
                break;
            default:
                # code...
                break;
	    }
        }
        $gamelist=json_encode($this->gamelist);
        $balancelist = Usersmoney::getUserBalance(Auth::id());

        $lang = $this->showlang;
        return view($this->path . '.member.trans_record' , compact('gamelist','balancelist','lang'));
    }

    public function betRecord(Request $request)
    {
        if ($request->isMethod('post')) {

            $data = $request->all();
            $start = $end = '';
            if (isset($data['time'])) {
                list($start, $end) = [$data['time'][0], $data['time'][1]];
            }
            $api_type = $data['api_type'];
            $api_type = ($api_type[0]!="all") ? $data['api_type'][1] : '' ;
            $limit = $data['limit'] ?? 10;
            $type = $data['type'] ?? '';
            
            $model = GameRecord::where('user_id', Auth::id())
                ->when($start, function ($query) use ($start) {
                    return $query->where('bet_time', '>=', $start);
                })->when($end, function ($query) use ($end) {
                    return $query->where('bet_time', '<=', $end);
                })->when($api_type, function ($query) use ($api_type) {
                    return $query->where('platform_type', '=', $api_type);
                })->when($type, function ($query) use ($type) {
                    return $query->where('platform_type', '=', $type);
                })->orderBy('id', 'desc');
            $list = $model->paginate($limit);
            $sum = $model->sum('bet_amount');

            // $tg = new TgService;
            // $user = Auth::user();
            // $ret = $tg->gameRecords($user->username, strtolower($api_type), $data['type'], $data['page'], $start, $end);
            $status = [1 => '已结算', 2 =>  '未结算', 0 => '无效注单'];
            $gamelist =$this->game_list;
            foreach ($list as &$val){
                $val['Code'] = isset($gamelist[$val['platform_type']]) ? $gamelist[$val['platform_type']] : $val['platform_type'];
                $val['_status'] = $status[$val['status']];
            }
            return $this->returnMsg(200, ['list' => $list,'sum' => $sum]);

        }
        $lang = $this->showlang;
        $gamelist=json_encode($this->gamelist);
        $balancelist = Usersmoney::getUserBalance(Auth::id());
        return view($this->path . '.member.bet_record' , compact('gamelist','balancelist','lang'));

    }

    public function userRedPacket(Request $request)
    {


        list($start, $end) = [date('Y-m-d').' 00:00:00',date('Y-m-d').' 23:59:59'];

        $acquirednum = Userredpacket::where('uid', Auth::id())
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->count();

        $totalRecharge = Recharge::where('user_id', Auth::id())->where('state',2)->where('pay_way','<>',10)
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->sum('amount');
        //return $this->returnMsg(200, $list);
        // $condition  = array(['amount'=>100000,'nums'=>10],['amount'=>50000,'nums'=>8],['amount'=>10000,'nums'=>5],['amount'=>5000,'nums'=>3],['amount'=>1000,'nums'=>1]);
        $rule = RedEnvelopes::where('flow_money','>=',$totalRecharge)->where('day_flow','<=',$totalRecharge)
            ->where('start_time','<',date('Y-m-d H:i:s'))->where('end_time','>',date('Y-m-d H:i:s'))->where('status',1)->orderBy('recharge','desc')->first();
        if (!$rule) {
            $sendnums = 0;
        } else {
            $sendnums = $rule->recharge;
        }
        $sendnums = $sendnums - $acquirednum;

        $rules = RedEnvelopes::where('status',1)->get();
        $data = date('Y-m-d');
        $datatime = date('Y-m-d H:i:s');
        $redPacketStatus = "READY";
        
        $max_times = RedEnvelopes::where('status',1)->orderBy('recharge','desc')->value('recharge');
        $max_end_time = RedEnvelopes::where('status',1)->orderBy('end_time','desc')->value('end_time');
        $min_start_time = RedEnvelopes::where('status',1)->orderBy('start_time')->value('start_time');
        if (!$rule) {
            $redPacketStatus = "END";
        } else {
            if (time() < strtotime($max_end_time) && time() > strtotime($min_start_time)) {
                $redPacketStatus = "STARTING";
            } elseif (time() > strtotime($max_end_time)) {
                $redPacketStatus = "END";
            } elseif (time() < strtotime($min_start_time)) {
                $redPacketStatus = "READY";
            }
        }
        
        // if (date('H')>14 && date('H')<16){
        //     $redPacketStatus = "STARTING";
        // }elseif(date('H')>=16){
        //     $redPacketStatus = "END";
        // }elseif(date('H')>0 && date('H')<14){
        //     $redPacketStatus = "READY";
        // }
        $lang = $this->showlang;
        return view($this->path . '.member.userreadpacket' , compact('sendnums','acquirednum','data','datatime','lang','redPacketStatus','rules','max_times'));
    }

    public function doUserRedPacket(Request $request)
    {

        $data = $request->all();
        list($start, $end) = [date('Y-m-d').' 00:00:00',date('Y-m-d').' 23:59:59'];

        $time = date('Y-m-d H:i:s');

        // if($time<$start || $time>$end){
        //     return $this->returnMsg(202, '','时间未到或者已过，无法领取');
        // }
        // if(time()-($data['time']/1000)>3){
        //     return $this->returnMsg(203, '','非法操作');
        // }
        $acquirednum = Userredpacket::where('uid', Auth::id())
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->count();

        $totalRecharge = Recharge::where('user_id', Auth::id())->where('state',2)
            ->where('pay_way','<>',10)
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->sum('amount');
        // $condition  = array(['amount'=>100000,'nums'=>10],['amount'=>50000,'nums'=>8],['amount'=>10000,'nums'=>5],['amount'=>5000,'nums'=>3],['amount'=>1000,'nums'=>1]);
        $rule = RedEnvelopes::where('flow_money','>=',$totalRecharge)->where('day_flow','<=',$totalRecharge)
            ->where('start_time','<',date('Y-m-d H:i:s'))->where('end_time','>',date('Y-m-d H:i:s'))->orderBy('recharge','desc')->first();
        if (!$rule) return $this->returnMsg(500,[],'暂无红包可抢');
        $sendnums = (int)$rule->recharge;
 
        if($sendnums<=0){
            return $this->returnMsg(203, '','累计充值不满足活动条件，无法领取');
        }
        if($acquirednum>=$sendnums){
            return $this->returnMsg(203, '','领取次数已超过奖励次数，无法领取');
        }
        //$redpacketmoney = $totalRecharge*0.02;

        $redpacketmoney = $this->randFloat(1,bcdiv($totalRecharge*$rule->money,100,2));

        $userinfo = Users::where('id', Auth::id())->lockForUpdate()->first();
        $userinfo->balance = $userinfo->balance + $redpacketmoney;
        $userinfo->save();
/*        $userredpacket->status = 1;
        $userredpacket->usetime = date('Y-m-d H:i:s');
        $userredpacket->save();*/
        $arrs = [
            'redpacketid' => $rule->id,
            'redpacketfee' => $rule->money,
            'uid' => $userinfo->id,
            'money' => $totalRecharge,
            'status' => 1,
            'redpacketmoney' =>$redpacketmoney,
            'usetime' => date('Y-m-d H:i:s'),
            'isuse' => 1
        ];
        Userredpacket::create($arrs);
        $arr = [
            'order_no' => date('Ymd') . '_' . $userinfo->id . '_' . time(),
            'api_type' => 'web',
            'user_id' => $userinfo->id,
            'transfer_type' => 5,
            'money' => $redpacketmoney,
            'cash_fee' => 0,
            'real_money' => $redpacketmoney,
            'before_money' => $userinfo->balance - $redpacketmoney,
            'after_money' => $userinfo->balance,
            'state' => 1
        ];
        TransferLog::create($arr);
        return $this->returnMsg(200, array('redpacketmoney'=>$redpacketmoney,'sendnums'=>$sendnums-$acquirednum-1,'acquirednum'=>$acquirednum+1), '成功领取');

    }

    public function randFloat($min = 0, $max = 1) {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return floatval(number_format($rand,2));
    }
    public function redPacket(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $start = $end = '';
            if (isset($data['time'])) {
                list($start, $end) = [$data['time'][0], $data['time'][1]];
            }

            $list = Userredpacket::where('uid', Auth::id())
                ->when($start, function ($query) use ($start) {
                    return $query->where('created_at', '>=', $start);
                })->when($end, function ($query) use ($end) {
                    return $query->where('created_at', '<=', $end);
                })->orderBy('id', 'desc')->paginate(10);

            foreach ($list as $k => $v) {
                $list[$k]['amount'] = $v['redpacketmoney'];
            }

            return $this->returnMsg(200, $list);

        }
        $gamelist=json_encode($this->gamelist);
        $lang = $this->showlang;
        return view($this->path . '.member.redpacket' , compact('gamelist','lang'));
    }

    public function getRedPacket(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $id = $data['id'];
            try {
                if ($id > 0) {
                    $userredpacket = Userredpacket::where('uid', Auth::id())->where('id', $id)->lockForUpdate()->first();
                    if ($userredpacket && !$userredpacket->status) {
                        $userinfo = Users::where('id', Auth::id())->lockForUpdate()->first();
                        $userinfo->balance = $userinfo->balance + $userredpacket->redpacketmoney;
                        $userinfo->save();
                        $userredpacket->status = 1;
                        $userredpacket->usetime = date('Y-m-d H:i:s');
                        $userredpacket->save();
                        $arr = [
                            'order_no' => date('Ymd') . '_' . $userinfo->id . '_' . time(),
                            'api_type' => 'web',
                            'user_id' => $userinfo->id,
                            'transfer_type' => 5,
                            'money' => $userredpacket->redpacketmoney,
                            'cash_fee' => 0,
                            'real_money' => $userredpacket->redpacketmoney,
                            'before_money' => $userinfo->balance - $userredpacket->redpacketmoney,
                            'after_money' => $userinfo->balance,
                            'state' => 1
                        ];
                        TransferLog::create($arr);
                        /*                    $Gamereport = new GamereportService();
                                            $datae['uid'] = $userinfo->id;
                                            $datae['pid'] = $userinfo->pid;
                                            $datae['isagent'] = $userinfo->isagent;
                                            $datae['redpackectnum'] = 1;
                                            $datae['totalredpackect'] = $userredpacket->redpacketmoney;
                                            $Gamereport->add($datae);*/
                        return $this->returnMsg(200, '', '成功领取');
                    }
                }else{

                    $userfanshui = Userredpacket::where('uid', Auth::id())->where('state',0)->lockForUpdate()->sum('redpacketmoney');
                    if ($userfanshui) {
                        $userinfo = Users::where('id', Auth::id())->lockForUpdate()->first();
                        $userinfo->balance = $userinfo->balance + $userfanshui;
                        $userinfo->save();

                        Userredpacket::where('uid', Auth::id())
                            ->where('state',0)
                            ->update(['state' => 1,'usetime'=>date('Y-m-d H:i:s')]);

                        /*
                                                $Gamereport = new GamereportService();
                                                $datae['uid'] = $userinfo->id;
                                                $datae['pid'] = $userinfo->pid;
                                                $datae['isagent'] = $userinfo->isagent;
                                                $datae['releasewater'] = 1;
                                                $datae['totalredpackect'] = $userfanshui;
                                                $Gamereport->add($datae);*/

                        return $this->returnMsg(200, '', '成功领取');
                    }else{
                        return $this->returnMsg(202, '', '没有可领取的返水');
                    }

                }
            } catch (\Exception $e) {
                return $this->returnMsg(202, '', '领取失败');
            }

        }
    }


    public function getfanshui(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $id = $data['id'];

            if ($id > 0) {
                $userfanshui = TransferLog::where('user_id', Auth::id())->where('id', $id)->where('transfer_type', 6)->lockForUpdate()->first();
                if ($userfanshui && !$userfanshui->state) {
                    $userinfo = Users::where('id', Auth::id())->lockForUpdate()->first();

                    $userinfo->balance = $userinfo->balance + $userfanshui->real_money;
                    $userinfo->save();

                    $userfanshui->state = 1;
                    $userfanshui->updated_at = date('Y-m-d H:i:s');
                    $userfanshui->before_money = $userinfo->balance - $userfanshui->real_money;
                    $userfanshui->after_money = $userinfo->balance;
                    $userfanshui->save();

                    /*
                                            $Gamereport = new GamereportService();
                                            $datae['uid'] = $userinfo->id;
                                            $datae['pid'] = $userinfo->pid;
                                            $datae['isagent'] = $userinfo->isagent;
                                            $datae['releasewater'] = 1;
                                            $datae['totalredpackect'] = $userfanshui->redpacketmoney;
                                            $Gamereport->add($datae);*/

                    return $this->returnMsg(200, '', '成功领取');
                }
            } else {

                $userfanshui = TransferLog::where('user_id', Auth::id())->where('state', 0)->where('transfer_type', 6)->lockForUpdate()->sum('real_money');
                if ($userfanshui) {
                    $userinfo = Users::where('id', Auth::id())->lockForUpdate()->first();
                    $userinfo->balance = $userinfo->balance + $userfanshui;
                    $userinfo->save();

                    TransferLog::where('user_id', Auth::id())
                        ->where('state', 0)
                        ->update(['state' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

                    /*
                                            $Gamereport = new GamereportService();
                                            $datae['uid'] = $userinfo->id;
                                            $datae['pid'] = $userinfo->pid;
                                            $datae['isagent'] = $userinfo->isagent;
                                            $datae['releasewater'] = 1;
                                            $datae['totalredpackect'] = $userfanshui;
                                            $Gamereport->add($datae);*/

                    return $this->returnMsg(200, '', '成功领取');
                } else {
                    return $this->returnMsg(202, '', '没有可领取的返水');
                }

            }


        }
    }

    public function fanshui(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $start = $end = '';
            if (isset($data['time'])) {
                list($start, $end) = [$data['time'][0], $data['time'][1]];
            }
            $api_type = $data['api_type'];
            $type =  ($data['type']==0) ? '' :  $data['type'] ;
            $api_type = ($api_type[0]!="all") ? $data['api_type'][1] : '' ;
            $limit = $data['limit'] ?? 10;

            $list = TransferLog::where('user_id', Auth::id())->where('transfer_type', 6)
                ->when($start, function ($query) use ($start) {
                    return $query->where('created_at', '>=', $start);
                })->when($end, function ($query) use ($end) {
                    return $query->where('created_at', '<=', $end);
                })->when($api_type, function ($query) use ($api_type) {
                    return $query->where('platform_type', '=', $api_type);
                })->when($type, function ($query) use ($type) {
                    return $query->where('state', '=', $type);
                })->orderBy('id', 'desc')->paginate($limit);

            $gamelist =$this->game_list;
            foreach ($list as $k => $v) {
                $list[$k]['gamename'] = isset($gamelist[$v['platform_type']]) ? $gamelist[$v['platform_type']] : $v['platform_type'];
                $list[$k]['_state'] = $v['state'] == 1 ? '已领取' : '未领取';
                $list[$k]->receive_time = $v['state'] == 1 ? date('Y-m-d H:i:s',strtotime($v->updated_at)) : '暂未领取';
            }

            return $this->returnMsg(200, $list);

        }
        $gamelist=$this->gamelist;
        $balancelist = Usersmoney::getUserBalance(Auth::id());
        $lang = $this->showlang;
        $userfanshui = TransferLog::where('user_id', Auth::id())->where('state', 0)->where('transfer_type', 6)->lockForUpdate()->sum('real_money');
        $userfanshui_has = TransferLog::where('user_id', Auth::id())->where('state', 1)->where('transfer_type', 6)->lockForUpdate()->sum('real_money');
        return view($this->path . '.member.fanshui' , compact('gamelist','balancelist','lang','userfanshui','userfanshui_has'));
    }

    public function activity($id){

        $activity = Activity::where('id',$id)->first();
        $lang = $this->showlang;
        return view($this->path . '.activityapply',compact("activity","id",'lang'));
    }

    public function doactivity(Request $request){

        if ($request->isMethod('post')) {
            $data = $request->all();
            if(empty($data['account'])){
                return $this->returnMsg(202, '', '请输入会员帐号');
            }

            $userinfo = Users::where('username', $data['account'])->first();
            if(empty($userinfo)){
                return $this->returnMsg(202, '', '会员帐号输入错误');
            }

            $activity = Activity::where('id', $data['activityid'])->first();
            if(empty($activity)){
                return $this->returnMsg(202, '', '活动不存在');
            }

            $isapple = ActivityApply::where("user_id",$userinfo->id)->first();
            if($isapple){
                return $this->returnMsg(202, '', '您已经申请过，无须重复申请');
            }

            $arr['activity_id'] = $data['activityid'];
            $arr['user_id'] = $userinfo->id;
            $arr['state'] = 1;
            $arr['created_at'] = time();
            $arr['updated_at'] = time();
            if(ActivityApply::create($arr)){
                return $this->returnMsg(200, '', '申请成功');
            }else{
                return $this->returnMsg(200, '', '申请失败');
            }

        }

    }

    public function progress(Request $request){
        if ($request->isMethod('post')) {
            $state = [1 => '待审核',2 => '通过',3 => '拒绝'];
            $limit = $request->input('limit');
            $activity = ActivityApply::where('user_id',Auth::id())->paginate($limit);
            foreach ($activity as &$val){
                $val['activityname'] = Activity::where('id',$val->activity_id)->value("title");
                $val['state'] = $state[$val['state']];
            }
            return $this->returnMsg(200, $activity, '申请成功');
        }
        return view($this->path . '.activityprogress');
    }

    public function uptransferstatus(Request $request){
        if ($request->isMethod('post')) {
            $data = $request->all();
            $user = Auth::user();
            $user->update($data);
            $tg = new TgService;
            $result = $tg->updateusertype($user->username,$data['transferstatus']);
     
            return $this->returnMsg(200, '', $result);
        }
    }
    
    /**
     * 领取升级奖励
     *
     * @param Request $request
     * @return void
     */
    public function claimUpgradeBonus(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                // 从请求头获取 API 令牌
                $token = $request->header('Authorization');
                $token = str_replace('Bearer ', '', $token);
                
                if (empty($token)) {
                    return $this->returnMsg(500, '', '用户未登录：令牌为空');
                }
                
                // 使用 DB 直接查询，减少 ORM 开销
                $user = DB::select('select * from users where api_token = ?', [$token]);
                
                if (empty($user)) {
                    return $this->returnMsg(500, '', '用户未登录：令牌无效');
                }
                
                $user = (object) $user[0];
                
                // 使用 DB 直接查询 VIP 等级信息
                $currentVip = DB::select('select * from user_vip where id = ? and status = 1', [$user->vip]);
                
                if (empty($currentVip) || $currentVip[0]->upgrade_bonus <= 0) {
                    return $this->returnMsg(500, '', '当前等级没有升级奖励');
                }
                
                $currentVip = (object) $currentVip[0];
                
                // 检查是否已经领取过升级奖励
                $hasClaimed = DB::select('select id from transfer_log where user_id = ? and transfer_type = ? and remark = ? limit 1', [$user->id, 7, '升级奖励']);
                
                if (!empty($hasClaimed)) {
                    return $this->returnMsg(500, '', '您已经领取过升级奖励');
                }
                
                // 检查是否达到当前 VIP 等级的要求
                if ($user->paysum < $currentVip->recharge || $user->totalgame < $currentVip->flow) {
                    return $this->returnMsg(500, '', '未达到当前等级的升级条件');
                }
                
                // 开始事务
                DB::beginTransaction();
                
                try {
                    // 发放奖励
                    $beforeBalance = $user->balance;
                    $newBalance = $beforeBalance + $currentVip->upgrade_bonus;
                    DB::update('update users set balance = ? where id = ?', [$newBalance, $user->id]);
                    
                    // 记录转账日志
                    $orderNo = date('Ymd') . '_' . $user->id . '_' . time();
                    DB::insert('insert into transfer_log (order_no, api_type, user_id, transfer_type, money, cash_fee, real_money, before_money, after_money, state, remark) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                        $orderNo, 'web', $user->id, 7, $currentVip->upgrade_bonus, 0, $currentVip->upgrade_bonus, $beforeBalance, $newBalance, 1, '升级奖励'
                    ]);
                    
                    // 提交事务
                    DB::commit();
                    
                    return $this->returnMsg(200, '', '成功领取升级奖励');
                } catch (Exception $e) {
                    // 回滚事务
                    DB::rollBack();
                    throw $e;
                }
            } catch (Exception $e) {
                return $this->returnMsg(500, '', '领取失败：' . $e->getMessage());
            }
        }
    }
    
    /**
     * 领取周奖励
     *
     * @param Request $request
     * @return void
     */
    public function claimWeeklySalary(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                // 从请求头获取 API 令牌
                $token = $request->header('Authorization');
                $token = str_replace('Bearer ', '', $token);
                
                if (empty($token)) {
                    return $this->returnMsg(500, '', '用户未登录');
                }
                
                // 根据令牌获取用户
                $user = DB::select('select * from users where api_token = ?', [$token]);
                
                if (empty($user)) {
                    return $this->returnMsg(500, '', '用户未登录');
                }
                
                // 转换为对象
                $user = (object) $user[0];
                
                $userVip = UserVip::where('id', $user->vip)->first();
                
                if (!$userVip || $userVip->weekly_salary <= 0) {
                    return $this->returnMsg(500, '', '当前等级没有周奖励');
                }
                
                // 检查今天是否是周一
                if (date('w') != 1) {
                    return $this->returnMsg(500, '', '周奖励只能在每周一领取');
                }
                
                // 检查是否已经领取过本周奖励
                $weekStart = date('Y-m-d', strtotime('this week'));
                $weekEnd = date('Y-m-d', strtotime('this week + 6 days'));
                
                $hasClaimed = TransferLog::where('user_id', $user->id)
                    ->where('transfer_type', 8) // 8 表示周奖励
                    ->where('remark', '周奖励')
                    ->whereBetween('created_at', [$weekStart . ' 00:00:00', $weekEnd . ' 23:59:59'])
                    ->exists();
                
                if ($hasClaimed) {
                    return $this->returnMsg(500, '', '您已经领取过本周奖励');
                }
                
                // 检查是否达到周奖励条件
                // 这里可以根据实际需求添加条件，例如周流水、周充值等
                
                // 发放奖励
                $user->balance += $userVip->weekly_salary;
                $user->save();
                
                // 记录转账日志
                TransferLog::create([
                    'order_no' => date('Ymd') . '_' . $user->id . '_' . time(),
                    'api_type' => 'web',
                    'user_id' => $user->id,
                    'transfer_type' => 8,
                    'money' => $userVip->weekly_salary,
                    'cash_fee' => 0,
                    'real_money' => $userVip->weekly_salary,
                    'before_money' => $user->balance - $userVip->weekly_salary,
                    'after_money' => $user->balance,
                    'state' => 1,
                    'remark' => '周奖励'
                ]);
                
                return $this->returnMsg(200, '', '成功领取周奖励');
            } catch (Exception $e) {
                return $this->returnMsg(500, '', '领取失败');
            }
        }
    }
    
    /**
     * 领取月奖励
     *
     * @param Request $request
     * @return void
     */
    public function claimMonthlySalary(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                // 从请求头获取 API 令牌
                $token = $request->header('Authorization');
                $token = str_replace('Bearer ', '', $token);
                
                if (empty($token)) {
                    return $this->returnMsg(500, '', '用户未登录');
                }
                
                // 根据令牌获取用户
                $user = DB::select('select * from users where api_token = ?', [$token]);
                
                if (empty($user)) {
                    return $this->returnMsg(500, '', '用户未登录');
                }
                
                // 转换为对象
                $user = (object) $user[0];
                
                $userVip = UserVip::where('id', $user->vip)->first();
                
                if (!$userVip || $userVip->monthly_salary <= 0) {
                    return $this->returnMsg(500, '', '当前等级没有月奖励');
                }
                
                // 检查今天是否是每月1号
                if (date('d') != 1) {
                    return $this->returnMsg(500, '', '月奖励只能在每月1号领取');
                }
                
                // 检查是否已经领取过本月奖励
                $monthStart = date('Y-m-01');
                $monthEnd = date('Y-m-t');
                
                $hasClaimed = TransferLog::where('user_id', $user->id)
                    ->where('transfer_type', 9) // 9 表示月奖励
                    ->where('remark', '月奖励')
                    ->whereBetween('created_at', [$monthStart . ' 00:00:00', $monthEnd . ' 23:59:59'])
                    ->exists();
                
                if ($hasClaimed) {
                    return $this->returnMsg(500, '', '您已经领取过本月奖励');
                }
                
                // 检查是否达到月奖励条件
                // 这里可以根据实际需求添加条件，例如月流水、月充值等
                
                // 发放奖励
                $user->balance += $userVip->monthly_salary;
                $user->save();
                
                // 记录转账日志
                TransferLog::create([
                    'order_no' => date('Ymd') . '_' . $user->id . '_' . time(),
                    'api_type' => 'web',
                    'user_id' => $user->id,
                    'transfer_type' => 9,
                    'money' => $userVip->monthly_salary,
                    'cash_fee' => 0,
                    'real_money' => $userVip->monthly_salary,
                    'before_money' => $user->balance - $userVip->monthly_salary,
                    'after_money' => $user->balance,
                    'state' => 1,
                    'remark' => '月奖励'
                ]);
                
                return $this->returnMsg(200, '', '成功领取月奖励');
            } catch (Exception $e) {
                return $this->returnMsg(500, '', '领取失败');
            }
        }
    }
    
    /**
     * 获取 VIP 特权数据
     *
     * @param Request $request
     * @return void
     */
    public function getVipPrivileges(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                $userVips = UserVip::where('status', 1)->get();
                $privileges = [];
                
                foreach ($userVips as $vip) {
                    // 使用 VIP 等级作为键，而不是使用 id 字段作为键
                    // 假设数据库中的 UserVip 记录的 id 字段直接对应 VIP 等级
                    $level = $vip->id;
                    $privileges[$level] = [
                        'upgrade_bonus' => $vip->upgrade_bonus,
                        'weekly_salary' => $vip->weekly_salary,
                        'monthly_salary' => $vip->monthly_salary
                    ];
                }
                
                return $this->returnMsg(200, $privileges, '获取成功');
            } catch (Exception $e) {
                return $this->returnMsg(500, '', '获取失败');
            }
        }
    }
    
    /**
     * 获取会员列表数据
     *
     * @param Request $request
     * @return void
     */
    public function getGameUsers(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                // 从请求头获取 API 令牌
                $token = $request->header('Authorization');
                $token = str_replace('Bearer ', '', $token);
                
                if (empty($token)) {
                    return $this->returnMsg(500, '', '用户未登录：令牌为空');
                }
                
                // 使用 DB 直接查询，减少 ORM 开销
                $user = DB::select('select * from users where api_token = ?', [$token]);
                
                if (empty($user)) {
                    return $this->returnMsg(500, '', '用户未登录：令牌无效');
                }
                
                $user = (object) $user[0];
                
                if ($user) {
                    $userData = [
                        'id' => $user->id,
                        'recharge' => $user->paysum,
                        'flow' => $user->totalgame,
                        'growth' => $user->growth || 0
                    ];
                    
                    return $this->returnMsg(200, [$userData], '获取成功');
                } else {
                    return $this->returnMsg(200, [], '获取成功');
                }
            } catch (Exception $e) {
                return $this->returnMsg(500, '', '获取失败：' . $e->getMessage());
            }
        }
    }
    
    /**
     * 获取用户信息
     *
     * @param Request $request
     * @return void
     */
    public function getUserInfo(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                $userInfo = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'realname' => $user->realname,
                    'balance' => $user->balance,
                    'vip' => $user->vip,
                    'isagent' => $user->isagent
                ];
                
                return $this->returnMsg(200, $userInfo, '获取成功');
            } else {
                return $this->returnMsg(500, '', '用户未登录');
            }
        } catch (xception $e) {
            return $this->returnMsg(500, '', '获取失败');
        }
    }
    
    // public function redPacket(Request $request)
    // {
    //     if ($request->isMethod('post')) {
    //         $data = $request->all();
    //         $start = $end = '';
    //         if (isset($data['time'])) {
    //             list($start, $end) = [$data['time'][0], $data['time'][1]];
    //         }

    //         $list = Userredpacket::where('uid', Auth::id())
    //             ->when($start, function ($query) use ($start) {
    //                 return $query->where('created_at', '>=', $start);
    //             })->when($end, function ($query) use ($end) {
    //                 return $query->where('created_at', '<=', $end);
    //             })->orderBy('id', 'desc')->paginate(10);

    //         foreach ($list as $k => $v) {
    //             $list[$k]['amount'] = $v['redpacketmoney'];
    //         }

    //         return $this->returnMsg(200, $list);

    //     }
    //     $gamelist=json_encode($this->gamelist);
    //     $lang = $this->showlang;
    //     return view($this->path . '.member.redpacket' , compact('gamelist','lang'));
    // }
    
    /**
     * 获取财务报表数据
     *
     * @param Request $request
     * @return void
     */
    /**
     * 获取关于我们内容
     *
     * @param Request $request
     * @return void
     */
    public function getAboutContent(Request $request)
    {
        try {
            // 尝试从数据库获取关于我们的内容
            try {
                // 检查数据库连接
                if (DB::connection()->getPdo()) {
                    // 尝试从articles表中查找标题为"关于我们"的文章
                    $article = DB::table('articles')->where('title', '关于我们')->first();
                    
                    if ($article && isset($article->content)) {
                        return response()->json([
                            'code' => 200,
                            'message' => '获取成功',
                            'data' => [
                                'content' => $article->content
                            ]
                        ]);
                    } else {
                        // 尝试从articlescate表中查找分类为"关于我们"的文章
                        $cate = DB::table('articlescate')->where('name', '关于我们')->first();
                        
                        if ($cate && isset($cate->id)) {
                            $article = DB::table('articles')->where('cateid', $cate->id)->first();
                            
                            if ($article && isset($article->content)) {
                                return response()->json([
                                    'code' => 200,
                                    'message' => '获取成功',
                                    'data' => [
                                        'content' => $article->content
                                    ]
                                ]);
                            }
                        }
                    }
                }
            } catch (Exception $dbEx) {
                // 数据库查询失败，继续执行，返回默认内容
            }
            
            // 如果数据库查询失败或没有找到内容，返回默认内容
            return response()->json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'content' => '<h1>关于我们</h1><p>这是关于我们的默认内容，您可以通过后台管理系统修改</p>'
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'code' => 500,
                'message' => '获取失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 代理分享页面
     *
     * @return void
     */
    public function agentFenxiang()
    {
        $lang = $this->showlang;
        $user = Auth::user();
        
        // 尝试多个可能的视图路径
        $viewPaths = [
            'web.agent_fenxiang',
            'web.template.mb12.agent_fenxiang',
            'web.template.mb12.member.agent_fenxiang',
        ];
        
        foreach ($viewPaths as $viewPath) {
            if (view()->exists($viewPath)) {
                return view($viewPath, compact('user','lang'));
            }
        }
        
        // 如果都找不到，直接返回简单的HTML
        return response()->view('web.agent_fenxiang_simple', compact('user','lang'));
    }

    /**
     * 获取阶梯奖励列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTierRewards(Request $request)
    {
        try {
            $userId = 0;
            $claimedIds = [];
            $currentInviteCount = 0;
            $directInvites = 0;
            $validInvites = 0;
            
            // 尝试获取用户信息
            $token = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $token);
            
            if (!empty($token)) {
                $puser = User::where('api_token', $token)->first();
                
                if ($puser) {
                    $userId = $puser->id;
                    
                    // 查询用户已领取的奖励
                    $claimedRewards = DB::select('select * from agent_fenxiang_claimed where user_id = ?', [$puser->id]);
                    $claimedIds = array_column($claimedRewards, 'reward_id');
                    
                    // 使用与IndexController完全相同的方式
                    // 下级代理数（所有层级）
                    $child_agent = User::getChildAgent($puser->id);
                    $currentInviteCount = count($child_agent);
                    
                    // 直属代理数
                    $directInvites = User::where('pid', $puser->id)->where('isagent', 1)->count();
                    
                    // 有效邀请（和下级代理数一致）
                    $validInvites = $currentInviteCount;
                }
            }
            
            // 查询阶梯奖励配置
            $rewards = DB::select('select * from agent_fenxiang where state = 1 order by sort_order asc, id asc');
            
            // 如果数据库中没有配置，返回默认示例数据
            if (empty($rewards)) {
                $formattedRewards = [
                    ['id' => 1, 'tier' => 1, 'inviteCount' => 1, 'reward' => 8, 'claimed' => false, 'oneTime' => true],
                    ['id' => 2, 'tier' => 2, 'inviteCount' => 2, 'reward' => 50, 'claimed' => false, 'oneTime' => true],
                    ['id' => 3, 'tier' => 3, 'inviteCount' => 1, 'reward' => 99, 'claimed' => false, 'oneTime' => true],
                ];
            } else {
                // 格式化数据
                $formattedRewards = [];
                foreach ($rewards as $index => $reward) {
                    $reward = (object) $reward;
                    $isClaimed = in_array($reward->id, $claimedIds);
                    $isAvailable = $currentInviteCount >= $reward->invite_count;
                    
                    // 检查下级代理是否满足最低充值金额要求
                    if ($isAvailable && $reward->min_recharge_amount > 0 && $userId > 0) {
                        // 查询所有下级代理的充值金额
                        $childAgents = User::where('pid', $userId)->where('isagent', 1)->get();
                        $allAgentsMeetRequirement = true;
                        
                        foreach ($childAgents as $childAgent) {
                            // 查询该代理的充值金额
                            $rechargeAmount = DB::select('select sum(amount) as total from recharge where user_id = ? and state = 2', [$childAgent->id]);
                            $totalRecharge = $rechargeAmount[0]->total ?? 0;
                            
                            if ($totalRecharge < $reward->min_recharge_amount) {
                                $allAgentsMeetRequirement = false;
                                break;
                            }
                        }
                        
                        $isAvailable = $allAgentsMeetRequirement;
                    }
                    
                    $formattedRewards[] = [
                        'id' => $reward->id,
                        'tier' => $index + 1,
                        'inviteCount' => $reward->invite_count,
                        'reward' => $reward->reward_amount,
                        'minRechargeAmount' => $reward->min_recharge_amount,
                        'claimed' => $isClaimed,
                        'oneTime' => $reward->one_time == 1,
                        'available' => $isAvailable && !$isClaimed
                    ];
                }
            }
            
            return $this->returnMsg(200, [
                'rewards' => $formattedRewards,
                'currentInviteCount' => $currentInviteCount,
                'directInvites' => $directInvites,
                'validInvites' => $validInvites,
            ], '获取成功');
            
        } catch (\Exception $e) {
            // 如果出错，返回默认示例数据
            $formattedRewards = [
                ['id' => 1, 'tier' => 1, 'inviteCount' => 1, 'reward' => 8, 'claimed' => false, 'oneTime' => true],
                ['id' => 2, 'tier' => 2, 'inviteCount' => 2, 'reward' => 50, 'claimed' => false, 'oneTime' => true],
                ['id' => 3, 'tier' => 3, 'inviteCount' => 1, 'reward' => 99, 'claimed' => false, 'oneTime' => true],
            ];
            return $this->returnMsg(200, [
                'rewards' => $formattedRewards,
                'currentInviteCount' => 0,
                'directInvites' => 0,
                'validInvites' => 0,
            ], '获取成功');
        }
    }

    /**
     * 获取分享信息
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShareInfo(Request $request)
    {
        try {
            $userId = 1;
            $username = (string) $userId;
            
            // 尝试获取用户信息
            $token = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $token);
            
            if (!empty($token)) {
                $user = DB::select('select * from users where api_token = ?', [$token]);
                if (!empty($user)) {
                    $user = (object) $user[0];
                    $userId = $user->id;
                    $username = $user->username ?? (string) $userId;
                }
            }
            
            $normalLink = $this->invitePublicUrl(SystemConfig::getValue('agent_wap_uri') ?: env('WAP_URL'), $userId);
            $wechatLink = $this->invitePublicUrl(SystemConfig::getValue('agent_wap_uri') ?: env('WAP_URL'), $userId, ['channel' => 'wechat']);
            
            return $this->returnMsg(200, [
                'normalLink' => $normalLink,
                'wechatLink' => $wechatLink,
                'rebateData' => [
                    'daily' => 0,
                    'weekly' => 0,
                    'monthly' => 0
                ],
                'inviteCode' => $username,
                'qrCodeUrl' => ''
            ], '获取成功');
            
        } catch (\Exception $e) {
            $userId = 1;
            $normalLink = $this->invitePublicUrl(SystemConfig::getValue('agent_wap_uri') ?: env('WAP_URL'), $userId);
            $wechatLink = $this->invitePublicUrl(SystemConfig::getValue('agent_wap_uri') ?: env('WAP_URL'), $userId, ['channel' => 'wechat']);
            
            return $this->returnMsg(200, [
                'normalLink' => $normalLink,
                'wechatLink' => $wechatLink,
                'rebateData' => [
                    'daily' => 0,
                    'weekly' => 0,
                    'monthly' => 0
                ],
                'inviteCode' => (string) $userId,
                'qrCodeUrl' => ''
            ], '获取成功');
        }
    }

    /**
     * 领取阶梯奖励
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function claimTierReward(Request $request)
    {
        DB::beginTransaction();
        try {
            // 从请求头获取 API 令牌
            $token = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $token);
            
            if (empty($token)) {
                return $this->returnMsg(500, [], '用户未登录：令牌为空');
            }
            
            // 查询用户
            $user = DB::select('select * from users where api_token = ?', [$token]);
            
            if (empty($user)) {
                return $this->returnMsg(500, [], '用户未登录：令牌无效');
            }
            
            $user = (object) $user[0];
            $tier = $request->input('tier');
            
            if (empty($tier)) {
                return $this->returnMsg(500, [], '奖励阶数不能为空');
            }
            
            // 查询阶梯奖励配置
            $rewards = DB::select('select * from agent_fenxiang where state = 1 order by sort_order asc, id asc');
            
            if (empty($rewards)) {
                return $this->returnMsg(500, [], '没有配置阶梯奖励');
            }
            
            // 检查tier是否有效
            if ($tier < 1 || $tier > count($rewards)) {
                return $this->returnMsg(500, [], '该阶奖励不存在');
            }
            
            // 获取对应阶数的奖励（tier从1开始）
            $reward = (object) $rewards[$tier - 1];
            $rewardId = $reward->id;
            
            if ($reward->state != 1) {
                return $this->returnMsg(500, [], '该奖励已禁用');
            }
            
            // 查询用户邀请的下级代理数量（只计算代理）
            $inviteCount = DB::select('select count(*) as count from users where pid = ? and isagent = 1', [$user->id]);
            $currentInviteCount = $inviteCount[0]->count ?? 0;
            
            if ($currentInviteCount < $reward->invite_count) {
                return $this->returnMsg(500, [], '邀请代理数量不足');
            }
            
            // 检查下级代理是否满足最低充值金额要求
            if ($reward->min_recharge_amount > 0) {
                // 查询所有下级代理的充值金额
                $childAgents = User::where('pid', $user->id)->where('isagent', 1)->get();
                $allAgentsMeetRequirement = true;
                
                foreach ($childAgents as $childAgent) {
                    // 查询该代理的充值金额
                    $rechargeAmount = DB::select('select sum(amount) as total from recharge where user_id = ? and state = 2', [$childAgent->id]);
                    $totalRecharge = $rechargeAmount[0]->total ?? 0;
                    
                    if ($totalRecharge < $reward->min_recharge_amount) {
                        $allAgentsMeetRequirement = false;
                        break;
                    }
                }
                
                if (!$allAgentsMeetRequirement) {
                    return $this->returnMsg(500, [], '下级代理未满足最低充值金额要求');
                }
            }
            
            // 检查是否已领取
            $claimed = DB::select('select * from agent_fenxiang_claimed where user_id = ? and reward_id = ?', [$user->id, $rewardId]);
            
            if (!empty($claimed)) {
                return $this->returnMsg(500, [], '该奖励已领取过了');
            }
            
            // 记录领取记录
            DB::insert('insert into agent_fenxiang_claimed (user_id, reward_id, reward_amount, created_at) values (?, ?, ?, ?)', [
                $user->id,
                $rewardId,
                $reward->reward_amount,
                date('Y-m-d H:i:s')
            ]);
            
            // 给用户加钱（使用ag_money字段）
            DB::update('update usersmoney set ag_money = ag_money + ? where user_id = ?', [
                $reward->reward_amount,
                $user->id
            ]);
            
            // 记录金额变动
            DB::insert('insert into transferlog (user_id, type, status, username, money, usermoney, created_at) values (?, ?, ?, ?, ?, ?, ?)', [
                $user->id,
                3,
                1,
                $user->username,
                $reward->reward_amount,
                0,
                date('Y-m-d H:i:s')
            ]);
            
            DB::commit();
            
            return $this->returnMsg(200, null, '领取成功！获得奖励 ' . $reward->reward_amount . ' 元');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnMsg(500, [], '领取失败：' . $e->getMessage());
        }
    }
    
    /**
     * 获取财务报表数据
     *
     * @param Request $request
     * @return void
     */
    public function getFinanceReport(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                // 从请求头获取 API 令牌
                $token = $request->header('Authorization');
                $token = str_replace('Bearer ', '', $token);
                
                if (empty($token)) {
                    return $this->returnMsg(500, '', '用户未登录：令牌为空');
                }
                
                // 使用 DB 直接查询，减少 ORM 开销
                $user = DB::select('select * from users where api_token = ?', [$token]);
                
                if (empty($user)) {
                    return $this->returnMsg(500, '', '用户未登录：令牌无效');
                }
                
                $user = (object) $user[0];
                
                $start_date = $request->input('start_date');
                $end_date = $request->input('end_date');
                
                // 查询充值数据
                $recharge = Recharge::where('user_id', $user->id)
                    ->where('state', 2)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('created_at', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('created_at', '<=', $end_date . ' 23:59:59');
                    })->sum('real_money');
                
                // 查询取款数据
                $withdrawal = Withdraw::where('user_id', $user->id)
                    ->where('state', 2)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('created_at', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('created_at', '<=', $end_date . ' 23:59:59');
                    })->sum('amount');
                
                // 查询投注数据
                $betAmount = GameRecord::where('user_id', $user->id)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('bet_time', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('bet_time', '<=', $end_date . ' 23:59:59');
                    })->sum('bet_amount');
                
                // 查询有效投注数据
                $validBet = GameRecord::where('user_id', $user->id)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('bet_time', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('bet_time', '<=', $end_date . ' 23:59:59');
                    })->sum('valid_amount');
                
                // 查询游戏返水数据
                $gameRebate = TransferLog::where('user_id', $user->id)
                    ->where('transfer_type', 6)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('created_at', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('created_at', '<=', $end_date . ' 23:59:59');
                    })->sum('real_money');
                
                // 查询代理佣金数据
                $agentCommission = TransferLog::where('user_id', $user->id)
                    ->where('transfer_type', 4)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('created_at', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('created_at', '<=', $end_date . ' 23:59:59');
                    })->sum('real_money');
                
                // 查询活动优惠数据
                $activityBonus = TransferLog::where('user_id', $user->id)
                    ->where('transfer_type', 5)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('created_at', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('created_at', '<=', $end_date . ' 23:59:59');
                    })->sum('real_money');
                
                // 查询游戏输赢数据
                $gameWinLoss = GameRecord::where('user_id', $user->id)
                    ->when($start_date, function ($query) use ($start_date) {
                        return $query->where('bet_time', '>=', $start_date);
                    })->when($end_date, function ($query) use ($end_date) {
                        return $query->where('bet_time', '<=', $end_date . ' 23:59:59');
                    })->sum('win_loss');
                
                // 计算净输赢
                $netWinLoss = $gameWinLoss + $gameRebate + $agentCommission + $activityBonus - $recharge + $withdrawal;
                
                // 构建返回数据
                $data = [
                    'recharge' => $recharge,
                    'withdrawal' => $withdrawal,
                    'betAmount' => $betAmount,
                    'validBet' => $validBet,
                    'gameRebate' => $gameRebate,
                    'agentCommission' => $agentCommission,
                    'activityBonus' => $activityBonus,
                    'gameWinLoss' => $gameWinLoss,
                    'netWinLoss' => $netWinLoss
                ];
                
                return $this->returnMsg(200, $data, '获取成功');
            } catch (Exception $e) {
                return $this->returnMsg(500, '', '获取失败：' . $e->getMessage());
            }
        }
    }

}
