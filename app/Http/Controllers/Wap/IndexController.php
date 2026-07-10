<?php

namespace App\Http\Controllers\Wap;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AgentApply;
use App\Models\Bank;
use App\Models\PaySetting;
use App\Models\Recharge;
use App\Models\Withdrawals;
use App\Models\TransferLog;
use App\Models\UserCard;
use App\Models\Users;
use App\Models\Message;
use App\Models\UserMessage;
use App\Models\Article;
use App\Models\Withdraw;
use App\Services\GamereportService;
use App\User;
use App\Models\Usersmoney;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\TgService;
use App\Models\Template;
use App\Models\CodePay;
use App\Models\SystemConfig;

class IndexController extends Controller
{
    protected $path;
    protected $state = [1 => '待审核', 2 => '通过', 3 => '失败'];
    public  $gamelist = array("bg"=>'BG真人','avia'=>"泛亚电竞",'vrbet'=>"VR彩票",'hlgame'=>"欢乐棋牌",'hbb'=>"新宝体育",'leg'=>"开元棋牌",'og'=>"OG真人",'ly'=>"乐游棋牌",'yb'=>"云博彩票"
            ,'99play'=>"99Play 电子游戏",'v8'=>"V8游戏",'hc'=>"皇朝游戏",'kx'=>"凯旋棋牌",'ig'=>"IG彩票",'dfw'=>"大富翁",'xsj'=>"新世界",'ld'=>"雷火电竞",'wm'=>"WM真人",'gg'=>"GG彩票"
            ,'dg'=>"DG游戲",'sbtest'=>"沙巴體育",'cmd'=>"CMD游戲",'oap'=>"三昇體育",'ae'=>"AE電子",'allBet'=>"欧博",'jdb'=>"jdb游戲",'fg'=>"FG游戲",'dt'=>"DT電子",'sy'=>"雙贏棋牌"
            ,'iggfc'=>"IG官方彩","iglottery"=>"IG时时彩票",'bgfish'=>'BG捕鱼','ag'=>'ag真人','bbdz'=>'BBIN电子','bbviode'=>'BBIN视讯','bbpe'=>'BBIN体育','bbinlottery'=>'BBIN彩票'
            ,'xsbo'=>'XSBO游戏','bbinfishone'=>'BBIN捕魚大師','bbinfishtwo'=>'BBIN捕魚達人','ia'=>'小艾电竞','ps'=>'PS游戏','bng'=>'BNG游戲','habaner'=>'HB游戲','jz'=>'极致彩票'
            ,'allbet'=>'欧博真人','zeus'=>'zeus区块链游戏','cg'=>'CG游戏','vg'=>'vg棋牌','tm'=>'TM棋牌'
            );
    public  $gamemoneylist;
    protected $engamelist = ['bg', 'avia', 'vrbet', 'hlgame', 'hbb', 'leg', 'qg', 'hc', 'play99', 'yb', 'ly', 'v8', 'kx', 'ig',
        'xsj', 'jdb', 'dg',  'pt', 'fq', 'wm','sbtest','cmd','ae','oap','ld','ia','sy','dt','xsbo','bbin','ag','allbet','ps','bng','habaner','jz','zeus','cg',
        'icg','pp','pg','sg','vg','tc','datqp','wg','tm','imone'];
    
    public function __construct()
    {
        $path = Template::where('client_type',2)->where('state',2)->first();
        $path = $path ? 'wap.template.'.$path->template_id : 'wap';
        $this->path = $path;
        $tg = New TgService;
        $this->gamemoneylist = $tg->gamesalllist();
    }

    public function index()
    {
        if(!$this->isMobile()){
            $wapurl = env("APP_URL");
            return redirect()->away($wapurl);
            exit;
        }         
       //
        $article = Article::where('cateid',6)->orderBy("id","desc")->first();
        return view($this->path.'.index',compact('article'));
    }

    public function activity()
    {
        $list = Activity::where('can_apply',1)->orderBy('id','desc')->get();
        return view($this->path.'.activity',compact('list'));
    }

    public function promotionEntry()
    {
        $path = public_path('new-h5/index.html');
        if (is_file($path)) {
            return response()->file($path);
        }

        return $this->activity();
    }

    public function showactivity($id)
    {
        $activity = Activity::where('id',$id)->first();
        return view($this->path.'.activityapply',compact('activity','id'));
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
    
    public function recharge()
    {
        $my_cards = UserCard::where('user_id',Auth::id())->get();
        // dd($my_cards);
        $card = PaySetting::where('state',1)->first();
        $wxinfo = CodePay::where('status',1)->where('id',3)->first();
        $usdtinfo = CodePay::where('status',1)->where('id',5)->first();
        $alipayinfo = CodePay::where('status',1)->where('id',4)->first();
        $cards = PaySetting::where('state',1)->get();
        $banklist = Bank::where('state', 1)->select('bank_name as label')->get();
        $usdt_rate = SystemConfig::getValue('usdt_rate');
        // dd($this->path);
        return view($this->path.'.recharge',compact('cards','card','usdtinfo','banklist','wxinfo','alipayinfo','my_cards','usdt_rate'));
    }
    
     public function withdrawals()
    {
        $cards = UserCard::where('user_id',Auth::id())->get();
        $card = PaySetting::where('state',1)->first();
        return view($this->path.'.withdrawals',compact('cards','card'));
    }
    
    

    public function center()
    {
        return view($this->path.'.center');
    }

    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $user = User::where('username',$data['name'])->first();
            if (!$user) return back()->with('opMsg','用户不存在');
            if (!Hash::check($data['password'],$user->password)) return back()->with('opMsg','密码错误');
            $user->lastip = $request->getClientIp();
            $user->logintime = time();
            $user->loginsum++;
            $user->save();
            Auth::login($user);
            return redirect('/');
        }
        return view($this->path.'.auth.login');
    }
    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
    public function register(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $user = User::where('username',$data['name'])->first();
            if ($user) return back()->with('opMsg','用户已存在');
            $tg = New TgService;
            $result = $tg->register($data['name']);
            if ($result['code'] != 200) {
                return back()->with('opMsg',$result['message']);
            }
            $arr = [
                'username' => $data['name'],
                'password' => Hash::make($data['password']),
                'realname' => $data['real_name'],
                'status' => 1,
                'paypwd' => Hash::make($data['qk_pwd']),
                'phone' => $data['phone'],
            ];
            $res = User::create($arr);
            Auth::login($res);
            return redirect('/');
        }
        return view($this->path.'.auth.register');
    }

    public function transfer()
    {
        $gamemoneylist =$this->gamemoneylist;
     
        return view($this->path.'.transfer',compact('gamemoneylist'));
    }

    public function addCard()
    {
        return view($this->path.'.add_card');
    }

    /**
     * 钱包
     * @return void
     */
    public function wallet()
    {
        $Balancelist = Usersmoney::getUserBalance(Auth::id());

        return view($this->path.'.wallet',compact('Balancelist'));
    }
    public function backWater(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $start = $end = '';
            if (isset($data['time'])) {
                list($start, $end) = [$data['time'][0], $data['time'][1]];
            }

            $list = TransferLog::where('user_id', Auth::id())->where('transfer_type', 6)
                ->when($start, function ($query) use ($start) {
                    return $query->where('created_at', '>=', $start);
                })->when($end, function ($query) use ($end) {
                    return $query->where('created_at', '<=', $end);
                })->orderBy('id', 'desc')->paginate(10)->toArray();
            foreach ($list['data'] as &$val){
                $val['platform_type'] = $this->gamelist[$val['platform_type']];
            }
            return $this->returnMsg(200, $list);

        }
        return view($this->path.'.back_water');
    }

    public function betRecord(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $start = $end = '';
            if (isset($data['time'])) {
                list($start, $end) = [$data['time'][0], $data['time'][1]];
            }

            $tg = new TgService;
            $user = Auth::user();
            $ret = $tg->gameRecords($user->username, '', '', $data['page'], $start, $end);
            foreach ($ret['data']['list'] as &$val){
                $val['Code'] = $this->gamelist[$val['Code']];
            }
            return $this->returnMsg(200, $ret);
        }
        return view($this->path . '.bet');
    }

    public function  transactionRecord(Request $request){
        $data = $request->all();

        if ($request->isMethod('post')) {
            $type = $data['type'];
            $start = $end = '';
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
                        })->orderBy('id', 'desc')->paginate(10);
                    foreach ($list as $k => $v) {
                        $list[$k]['state'] = $this->state[$v->state];

                        $list[$k]['type'] = ($v->pay_way==10) ? '充值赠送': '充值';
                    }
                    return $this->returnMsg(200, $list);
                    break;

                case 1://提现
                    $list = Withdraw::where('user_id', Auth::id())
                        ->when($start, function ($query) use ($start) {
                            return $query->where('created_at', '>=', $start);
                        })->when($end, function ($query) use ($end) {
                            return $query->where('created_at', '<=', $end);
                        })->orderBy('id', 'desc')->paginate(10);
                    foreach ($list as $k => $v) {
                        $list[$k]['state'] = $this->state[$v->state];
                        $list[$k]['type'] ='提现';

                    }
                    return $this->returnMsg(200, $list);
                    break;
                case 2://转账
                    $list = TransferLog::where('user_id', Auth::id())->whereIn('transfer_type', [0,1])
                        ->when($start, function ($query) use ($start) {
                            return $query->where('created_at', '>=', $start);
                        })->when($end, function ($query) use ($end) {
                            return $query->where('created_at', '<=', $end);
                        })->orderBy('id', 'desc')->paginate(10);
                    foreach ($list as $k => $v) {
                        //  $list[$k]['type'] = (($v['transfer_type']==0) ? '游戏转出' : '转入游戏').$this->gamenamelist[$v['api_type']];
                        $list[$k]['type'] = (($v['transfer_type']==0) ? '游戏转出' : '转入游戏');
                        $list[$k]['state'] = ($v['state']==1) ? '成功' : '失败';
                        $list[$k]['out_trade_no'] = $v['order_no'];
                        $list[$k]['amount'] = $v['money'];


                    }
                    return $this->returnMsg(200, $list);
                    break;
                default:
                    # code...
                    break;
            }
        }
        return view($this->path . '.transRecord',compact('type'));
    }

    public function  transactionRecord1(Request $request){
        return view($this->path . '.transRecord1');
    }
    public function  transactionRecord2(Request $request){
        return view($this->path . '.transRecord2');
    }
    public function  bankcard(Request $request){
        $banklist = Bank::where('state',1)->get();
        $cards = UserCard::where('user_id',Auth::id())->where('bank','<>','USDT')->get();
        return view($this->path . '.add_card',compact('banklist','cards'));
    }
    
    public function bindUsdt()
    {
        $usdt = UserCard::where('user_id',Auth::id())->where('bank','USDT')->first();
        return view($this->path.'.add_usdt',compact('usdt'));
    }

    public function  editpassword(Request $request){
        $user = Auth::user();
        return view($this->path . '.editpassword',compact('user'));
    }


    public function editPayPasswordDo(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();

        if (!Hash::check($data['old_password'],$user->password)) return $this->returnMsg(205);
        $user->paypwd = Hash::make($data['new_password']);
        $user->save();
        return $this->returnMsg(200);
    }

    public function editPasswordDo(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        if (!Hash::check($data['old_password'],$user->password)) return $this->returnMsg(205);
        $user->password = Hash::make($data['new_password']);
        $user->save();
        return $this->returnMsg(200);
    }

    public function  persondata(Request $request){
        $user = Auth::user();
        return view($this->path . '.persondata',compact('user'));
    }
    
    public function  applyagent(Request $request){
        $user = Auth::user();
        return view($this->path . '.applyagent',compact('user'));
    }


    public function applyagentdo(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $useragent = AgentApply::where('user_id',$user->id)->first();
         if ($useragent)return $this->returnMsg(500, '', '您已申请过代理'); 

            $arr = [
                'user_id' => $user->id,
                'apply_info' => $data['apply_info'],
                'state' => 1,
                'mobile' => $data['mobile'],
            ];
        if($res = AgentApply::create($arr)){
          return $this->returnMsg(200, '', '请选择转账方式');
        }else{
            return $this->returnMsg(500, '', '申请失败');
        }
            
        
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
    private function isAdult($time)
    {
        $time = strtotime($time);
        return (time() - $time > 18 * 365 * 86400) ? 1 : 0;
    }
    public function  messagecenter(Request $request){
         $user = Auth::user();
        
        // 构建查询条件
        $query = Message::query();
        
        // 基础条件：面向所有用户的消息
        $query->where(function($q) {
            $q->where('user_id', 0)
              ->where('vip_id', 0)
              ->where('isagent', 0);
        });
        
        // 如果是代理用户，可以看到代理消息
        if ($user->isagent == 1) {
            $query->orWhere('isagent', 1);
        }
        
        // 可以看到对应VIP等级的消息（排除VIP黑名单消息）
        $query->orWhere(function($q) use ($user) {
            $q->where('vip_id', $user->vip)
              ->where('isagent', '!=', 2);
        });
        
        // 可以看到专门发给自己的消息
        $query->orWhere('user_id', $user->id);
        
        $list = $query->paginate(20);
        foreach ($list as $k => $v) {
            $user_message = UserMessage::where('message_id', $v->id)->count();
            $list[$k]['is_read'] = $user_message ?? 0;
        }        
       
        return view($this->path . '.messagecenter',compact('list'));
    }
    public function getUserPlBalance(Request $request)
    {
        $type = $request->input('type') ?? '';
        $user = Auth::user();
        $tg = new TgService;
        $result = $tg->userBalance($user->username,$type);
        if ($result['code'] != 200) {
            return $this->returnMsg(500,[],$result['message']);
        }
        Usersmoney::upinfo($user->id,$type,$result['data']);
        return $this->returnMsg(200,$result['data']);
    }


    public function transferMoney(Request $request)
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
        $mon = explode('.',$money*100);
        if(count($mon)>1){
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

    public function transferUserPlBalance(Request $request)
    {
        $type = $request->input('type') ?? '';
        $user = Auth::user();
        $tg = new TgService;
        $result = $tg->userBalance($user->username,$type);
        if ($result['code'] != 200) {
            return $this->returnMsg(500,[],$result['message']);
        }
        Usersmoney::upinfo($user->id,$type,$result['data']);
        $ret = $user->Accounttranso($type, $result['data']);
        if ($ret['code'] == 200) {
            Usersmoney::upinfo($user->id,$type,0);
            return $this->returnMsg(200, '转出成功');
        } else {
            return $this->returnMsg(500, [], $ret['message']);
        }
    }

    public function backWaterAll(Request $request)
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


                    $Gamereport = new GamereportService();
                    $datae['uid'] = $userinfo->id;
                    $datae['pid'] = $userinfo->pid;
                    $datae['isagent'] = $userinfo->isagent;
                    $datae['releasewater'] = 1;
                    $datae['totalredpackect'] = $userfanshui->redpacketmoney;
                    $Gamereport->add($datae);
                    return $this->returnMsg(200, '', '成功领取');
                }
            }else{

                $userfanshui = TransferLog::where('user_id', Auth::id())->where('state',0)->where('transfer_type', 6)->lockForUpdate()->sum('real_money');
                if ($userfanshui) {
                    $userinfo = Users::where('id', Auth::id())->lockForUpdate()->first();
                    $userinfo->balance = $userinfo->balance + $userfanshui;
                    $userinfo->save();
                    TransferLog::where('user_id', Auth::id())
                        ->where('state',0)
                        ->update(['state' => 1,'updated_at'=>date('Y-m-d H:i:s')]);


                    $Gamereport = new GamereportService();
                    $datae['uid'] = $userinfo->id;
                    $datae['pid'] = $userinfo->pid;
                    $datae['isagent'] = $userinfo->isagent;
                    $datae['releasewater'] = 1;
                    $datae['totalredpackect'] = $userfanshui;
                    $Gamereport->add($datae);

                    return $this->returnMsg(200, '', '成功领取');
                }else{
                    return $this->returnMsg(202, '', '没有可领取的返水');
                }

            }

        }
    }
}
