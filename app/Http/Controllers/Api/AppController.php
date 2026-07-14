<?php
//decode by http://www.yunlu99.com/
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityApply;
use App\Models\ActivityType;
use App\Models\Bank;
use App\Models\Api;
use App\Models\User_Api;
use App\Models\Message;
use App\Models\UserMessage;
use App\Models\PaySetting;
use App\Models\SystemConfig;
use App\Models\UserCard;
use App\Models\User;
use App\Models\Users;
use App\Models\Usersmoney;
use App\Services\SafeGameTransferService;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TransferLog;
use App\Models\Recharge;
use App\Models\Withdraw;
use App\Models\Article;
use App\Models\UserVip;
use App\Models\Banner;
use App\Models\CodePay;
use App\Models\UsdtPay;
use App\Models\GameRecord;
use App\Models\AgentApply;
use App\Models\GameList;
use App\Models\GameListApp;
use App\Services\PromotionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use QrCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Services\PayService;
class AppController extends Controller
{
    protected $messages = [
        'password.required' => '密码不能为空',
        'password.min' => '密码6到12位',
		'password.max' => '密码6到12位',
        'name.required' => '账号不能为空',
        'qk_pwd.required' => '取款密码不能为空',
        'qk_pwd.min' => '取款密码6到12位',
		'qk_pwd.max' => '取款密码6到12位',
        'realname.required' => '真实姓名不能为空',		
    ];

    public function __construct()
    {
        $this->PayService = new PayService();
    }
    protected function activeUserFromApiToken($token, $lock = false)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }

        $query = User::where('api_token', $token);
        if ($lock) {
            $query->lockForUpdate();
        }

        $user = $query->first();
        if (!$user || $this->isBlockedApiUser($user)) {
            return null;
        }

        return $user;
    }

    protected function isBlockedApiUser(User $user)
    {
        return (int) ($user->status ?? 0) <= 0
            || (int) ($user->isdel ?? 0) === 1
            || (int) ($user->isblack ?? 0) === 1;
    }

    private function requestPlayerLevel(Request $request)
    {
        $token = $request->input('lastsession', '');
        if ($token === '') {
            $token = trim(preg_replace('/^Bearer\s+/i', '', (string) $request->header('authorization', '')));
        }

        $user = $this->activeUserFromApiToken($token);
        if (!$user) {
            return 0;
        }

        return max((int)($user->vip ?? 0), (int)($user->level ?? 0));
    }

    public function pay_list()
    {
		$data = array();
		$data['bank'] = 0;
		$data['alipay'] = 0;
		$data['weixin'] = 0;
		$data['usdt_trc20'] = 0;
		$data['usdt_erc20'] = 0;
		$data['cgpay'] = 0;
		$data['zxcgpay'] = 0;
		$PaySetting = PaySetting::where('state',1)->first();
		if($PaySetting){
			$data['bank'] = 1;
		}
		$CodePay = CodePay::where('status',1)->get()->toArray();
		foreach($CodePay as $key => $value){
			if (strstr( $value['content'] , '支付宝' ) !== false ){
                $data['alipay'] = 1;
			}
			if (strstr( $value['content'] , '微信' ) !== false ){
                $data['weixin'] = 1;
			}
			if (strstr( $value['content'] , 'TRC20' ) !== false ){
                $data['usdt_trc20'] = 1;
			}
			if (strstr( $value['content'] , 'ERC20' ) !== false ){
                $data['usdt_erc20'] = 1;
			}
			if (strstr( $value['content'] , 'CGPay转账' ) !== false ){
                $data['cgpay'] = 1;
			}
			if (strstr( $value['content'] , 'CGPay在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}	
	if (strstr( $value['content'] , 'KDPay在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}	
	if (strstr( $value['content'] , 'JDPay在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}	
	if (strstr( $value['content'] , 'TOPay在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}	
	if (strstr( $value['content'] , 'CBPay在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}	
	if (strstr( $value['content'] , '钱能在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}	
	if (strstr( $value['content'] , 'CGPay在线' ) !== false ){
                $data['zxcgpay'] = 1;
			}				
		}
		return $this->returnMsg(200,$data,'成功');
    }	
    public function open($code)
    {
		if($code == 'wechat'){
			$url = 'weixin://';			
		}else if($code == 'alipay'){
			$url = 'alipays://';	
		}else{
			$url = 'https://xy281.eu.cc/#/kefu';
		}
        header("Location:".$url);
		die();
    }	
    public function zxcgpay_pay(Request $request)
    {

        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		if($data['money'] == ''){
			return $this->returnMsg(201,'','请填写完整数据');
		}
		//$data['pay_id']固定参数：zxcgpay
		if($data['pay_id'] == ''){
			return $this->returnMsg(201,'','通道未开启，请切换其他充值方式');
		}		
		$CodePay = CodePay::where('status',1)->where('content','like','%CGPAY在线%')->first();
		if(!$CodePay){
			return $this->returnMsg(201,'','入款方式不存在,请重试或联系客服');
		}
		$money = (float)$data['money'];
		$cz_min = $CodePay->min_price;
		$cz_max = $CodePay->max_price;
		if($money < $cz_min || $money > $cz_max){
			return $this->returnMsg(201,'','单笔充值金额限制'.$cz_min.'元-'.$cz_max.'元');
		}	
        $out_trade_no = time().$user->id.rand(1000,9999);
        $datas['out_trade_no'] = $out_trade_no;
        $datas['user_id'] = $user->id;		
		$datas['amount'] = $money;
		$datas['pay_way'] = $CodePay->id;
		$datas['cash_fee'] = 0;
		$datas['real_money'] = $datas['amount'];
		$datas['usdt_rate'] = 0;
		$datas['state'] = 1;
		$pay = $this->PayService->cgpay($datas['out_trade_no'], $datas['amount'], $CodePay);
		if(!$pay){
			return $this->returnMsg(201,'','网络错误');
		}
		$pay = json_decode($pay,true);
		if(!is_array($pay)){
			return $this->returnMsg(201,'','数据解析失败');
		}
		if($pay['ReturnCode'] > 0){
			return $this->returnMsg(201,'',$pay['ReturnMessage']);
		}
		$res = Recharge::create($datas);
        return $this->returnMsg(200,$pay['Qrcode'],'成功');		
	}	
    public function wechat_pay(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		if($data['money'] == '' || $data['name'] == ''){
			return $this->returnMsg(201,'','请填写完整数据');
		}
		if($data['pay_id'] == ''){
			return $this->returnMsg(201,'','通道未开启，请切换其他充值方式');
		}		
		$CodePay = CodePay::where('status',1)->where('id',$data['pay_id'])->first();
		if(!$CodePay){
			return $this->returnMsg(201,'','入款方式不存在,请重试或联系客服');
		}
		$money = (float)$data['money'];
		$cz_min = $CodePay->min_price;
		$cz_max = $CodePay->max_price;
		if($money < $cz_min || $money > $cz_max){
			return $this->returnMsg(201,'','微信单笔充值金额限制'.$cz_min.'元-'.$cz_max.'元');
		}	
        $out_trade_no = time().$user->id.rand(1000,9999);
        $datas['out_trade_no'] = $out_trade_no;
        $datas['user_id'] = $user->id;		
		$datas['amount'] = $money;
		$datas['pay_way'] = $CodePay->id;
		$datas['bank_owner'] = $data['name'];
		$datas['cash_fee'] = 0;
		$datas['real_money'] = $datas['amount'];
		$datas['usdt_rate'] = 0;
		$datas['state'] = 1;
		
		$res = Recharge::create($datas);
        return $this->returnMsg(200,'','充值成功,请等待审核');		
	}	
    public function usdt_pay(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		if($data['money'] == ''){
			return $this->returnMsg(201,'','请填写完整数据');
		}
		if($data['pay_id'] == ''){
			return $this->returnMsg(201,'','通道未开启，请切换其他充值方式');
		}		
		$UsdtPay = UsdtPay::where('status',1)->where('id',$data['pay_id'])->first();
		if(!$UsdtPay){
			return $this->returnMsg(201,'','入款方式不存在,请重试或联系客服');
		}
		$money = (float)$data['money'];
		$cz_min = $UsdtPay->min_price;
		$cz_max = $UsdtPay->max_price;
		if($money < $cz_min || $money > $cz_max){
			return $this->returnMsg(201,'','单笔充值金额限制'.$cz_min.'元-'.$cz_max.'元');
		}	
        $out_trade_no = time().$user->id.rand(1000,9999);
        $datas['out_trade_no'] = $out_trade_no;
        $datas['user_id'] = $user->id;		
		$datas['amount'] = $money;
		$datas['pay_way'] = $UsdtPay->id;
		$datas['cash_fee'] = 0;
		$datas['real_money'] = $datas['amount'];
		$datas['usdt_rate'] = $UsdtPay->exchange_rate ?: SystemConfig::getValue('usdt_rate');
		$datas['state'] = 1;
		
		$res = Recharge::create($datas);
        return $this->returnMsg(200,'','充值成功,请等待审核');		
	}	
    public function cgpay_pay(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		if($data['money'] == ''){
			return $this->returnMsg(201,'','请填写完整数据');
		}
		if($data['pay_id'] == ''){
			return $this->returnMsg(201,'','通道未开启，请切换其他充值方式');
		}		
		$CodePay = CodePay::where('status',1)->where('id',$data['pay_id'])->first();
		if(!$CodePay){
			return $this->returnMsg(201,'','入款方式不存在,请重试或联系客服');
		}
		$money = (float)$data['money'];
		$cz_min = $CodePay->min_price;
		$cz_max = $CodePay->max_price;
		if($money < $cz_min || $money > $cz_max){
			return $this->returnMsg(201,'','单笔充值金额限制'.$cz_min.'元-'.$cz_max.'元');
		}	
        $out_trade_no = time().$user->id.rand(1000,9999);
        $datas['out_trade_no'] = $out_trade_no;
        $datas['user_id'] = $user->id;		
		$datas['amount'] = $money;
		$datas['pay_way'] = $CodePay->id;
		$datas['cash_fee'] = 0;
		$datas['real_money'] = $datas['amount'];
		$datas['usdt_rate'] = 1;
		$datas['state'] = 1;
		
		$res = Recharge::create($datas);
        return $this->returnMsg(200,'','充值成功,请等待审核');		
	}	
    public function wechat_info(Request $request)
    {
		$data = array();
		$data['pay_id'] = '';
		$data['bank_name'] = '';
		$data['account'] = '';
		$data['qrcodeurl'] = '';
		$CodePay = CodePay::where('status',1)->where('content','like','%微信%')->first();
		if($CodePay){
			$data['pay_id'] = $CodePay->id;
			$data['bank_name'] = $CodePay->content;
			$data['account'] = $CodePay->mch_id;
			$data['qrcodeurl'] = env('APP_URL').'/uploads/'.$CodePay->payimg;		
		}
		return $this->returnMsg(200,$data,'成功');
	}

    public function cgpay_info(Request $request)
    {
		$data = array();
		$data['pay_id'] = '';
		$data['bank_name'] = '';
		$data['account'] = '';
		$data['qrcodeurl'] = '';
		$CodePay = CodePay::where('status',1)->where('content','like','%CGPay转账%')->first();
		if($CodePay){
			$data['pay_id'] = $CodePay->id;
			$data['bank_name'] = $CodePay->content;
			$data['account'] = $CodePay->mch_id;
			$data['qrcodeurl'] = env('APP_URL').'/uploads/'.$CodePay->payimg;		
		}
		return $this->returnMsg(200,$data,'成功');
	}
	
    public function usdt_info(Request $request)
    {
		$post = $request->all();
		if($post['paytype'] == ''){   //TRC20   ERC20
			return $this->returnMsg(201,'','通道不存在或已维护');
		}
		$usdt_rate = SystemConfig::getValue('usdt_rate');  //USDT汇率
		$where = 'USDT-'.$post['paytype'];
		$data = array();
		$data['pay_id'] = '';
		$data['bank_name'] = '';
		$data['account'] = '';
		$data['qrcodeurl'] = '';
		$data['usdt_rate'] = $usdt_rate;
		$CodePay = CodePay::where('status',1)->where('content',$where)->first();
		if($CodePay){
			$data['pay_id'] = $CodePay->id;
			$data['bank_name'] = $CodePay->content;
			$data['account'] = $CodePay->mch_id;
			$data['qrcodeurl'] = env('APP_URL').'/uploads/'.$CodePay->payimg;		
		}
		return $this->returnMsg(200,$data,'成功');
	}
	
    public function alipay_pay(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		if($data['money'] == '' || $data['name'] == ''){
			return $this->returnMsg(201,'','请填写完整数据');
		}
		if($data['pay_id'] == ''){
			return $this->returnMsg(201,'','通道未开启，请切换其他充值方式');
		}		
		$CodePay = CodePay::where('status',1)->where('id',$data['pay_id'])->first();
		if(!$CodePay){
			return $this->returnMsg(201,'','入款方式不存在,请重试或联系客服');
		}
		$money = (float)$data['money'];
		$cz_min = $CodePay->min_price;
		$cz_max = $CodePay->max_price;
		if($money < $cz_min || $money > $cz_max){
			return $this->returnMsg(201,'','支付宝单笔充值金额限制'.$cz_min.'元-'.$cz_max.'元');
		}	
        $out_trade_no = time().$user->id.rand(1000,9999);
        $datas['out_trade_no'] = $out_trade_no;
        $datas['user_id'] = $user->id;		
		$datas['amount'] = $money;
		$datas['pay_way'] = $CodePay->id;
		$datas['bank_owner'] = $data['name'];
		$datas['cash_fee'] = 0;
		$datas['real_money'] = $datas['amount'];
		$datas['usdt_rate'] = 0;
		$datas['state'] = 1;
		
		$res = Recharge::create($datas);
        return $this->returnMsg(200,'','充值成功,请等待审核');		
	}	
    public function alipay_info(Request $request)
    {
		$data = array();
		$data['pay_id'] = '';
		$data['bank_name'] = '';
		$data['account'] = '';
		$data['qrcodeurl'] = '';
		$CodePay = CodePay::where('status',1)->where('content','like','%支付宝%')->first();
		if($CodePay){
			$data['pay_id'] = $CodePay->id;
			$data['bank_name'] = $CodePay->content;
			$data['account'] = $CodePay->mch_id;
			$data['qrcodeurl'] = env('APP_URL').'/uploads/'.$CodePay->payimg;		
		}
		return $this->returnMsg(200,$data,'成功');
	}	
    public function recharge_list(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
        $page = $data['page'];
        $start_at = date('Y-m-d H:i:s', time() - 60 * 1440 * 30);
		$end_at = date('Y-m-d H:i:s');
		$Recharge = Recharge::where("user_id",$user->id)->select("amount","state","created_at")->whereBetween('created_at', [$start_at, $end_at])->orderBy('id', 'desc')->paginate(20,['*'],'page',$page)->toArray();
		$Recharge = $Recharge['data'];
		foreach($Recharge as $key => $value){
			if($value['state'] == 1){
				$Recharge[$key]['state'] = '审核中';
			}
			if($value['state'] == 2){
				$Recharge[$key]['state'] = '<font color="green">已通过</font>';
			}
			if($value['state'] == 3){
				$Recharge[$key]['state'] = '<font color="red">未通过</font>';
			}			
		}
		return $this->returnMsg(200,$Recharge,'成功');
	}
    public function bank_pay(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		if($data['money'] == '' || $data['name'] == ''){
			return $this->returnMsg(201,'','请填写完整数据');
		}
		if($data['pay_id'] == ''){
			return $this->returnMsg(201,'','通道未开启，请切换其他充值方式');
		}		
		$PaySetting = PaySetting::where('state',1)->where('id',$data['pay_id'])->first();
		if(!$PaySetting){
			return $this->returnMsg(201,'','入款卡号不存在,请重试或联系客服');
		}
		$money = (float)$data['money'];
		$cz_min = SystemConfig::getValue('min_price');
		$cz_max = SystemConfig::getValue('max_price');
		if($money < $cz_min || $money > $cz_max){
			return $this->returnMsg(201,'','银行卡单笔充值金额限制'.$cz_min.'元-'.$cz_max.'元');
		}	
        $out_trade_no = time().$user->id.rand(1000,9999);
        $datas['out_trade_no'] = $out_trade_no;
        $datas['user_id'] = $user->id;		
		$datas['amount'] = $money;
		$datas['pay_way'] = $PaySetting->id;
		$datas['bank_owner'] = $data['name'];
		$datas['cash_fee'] = 0;
		$datas['real_money'] = $datas['amount'];
		$datas['usdt_rate'] = 0;
		$datas['state'] = 1;
		
		$res = Recharge::create($datas);
        return $this->returnMsg(200,'','充值成功,请等待审核');		
	}
	
    public function getbank_info(Request $request)
    {
		$data = array();
		$data['pay_id'] = '';
		$data['bank_name'] = '';
		$data['username'] = '';
		$data['card_no'] = '';
		$data['bank_address'] = '';
		$PaySetting = PaySetting::where('state',1)->first();
		if($PaySetting){
			$Bank = Bank::where('id',$PaySetting->bank_id)->first();
			if($Bank){
				$data['pay_id'] = $PaySetting->id;
				$data['bank_name'] = $Bank->bank_name;
				$data['username'] = $PaySetting->bank_owner;
				$data['card_no'] = $PaySetting->bank_no;
				$data['bank_address'] = $PaySetting->bank_address;		
			}
		}
		return $this->returnMsg(200,$data,'成功');
	}
	
    public function cash_list(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
        $page = $data['page'];
        $start_at = date('Y-m-d H:i:s', time() - 60 * 1440 * 30);
		$end_at = date('Y-m-d H:i:s');
		$Withdraw = Withdraw::where("user_id",$user->id)->select("amount","state","created_at")->whereBetween('created_at', [$start_at, $end_at])->orderBy('id', 'desc')->paginate(20,['*'],'page',$page)->toArray();
		$Withdraw = $Withdraw['data'];
		foreach($Withdraw as $key => $value){
			if($value['state'] == 1){
				$Withdraw[$key]['state'] = '审核中';
			}
			if($value['state'] == 2){
				$Withdraw[$key]['state'] = '<font color="green">已通过</font>';
			}
			if($value['state'] == 3){
				$Withdraw[$key]['state'] = '<font color="red">未通过</font>';
			}			
		}
		return $this->returnMsg(200,$Withdraw,'成功');
	}
	
    public function post_drawing(Request $request)
    {
        $data = $request->all();
        $token = $data['lastsession'] ?? trim(preg_replace('/^Bearer\s+/i', '', (string) $request->header('authorization', '')));
        $user = $this->activeUserFromApiToken($token);
        if (!$user) {
            return $this->returnMsg(201, '', 'login expired');
        }

        if (!isset($data['money']) || !is_numeric($data['money'])) {
            return $this->returnMsg(201, '', 'invalid amount');
        }
        $amount = round((float) $data['money'], 2);
        if ($amount <= 0) {
            return $this->returnMsg(201, '', 'invalid amount');
        }

        $bankId = (int)($data['bankid'] ?? 0);
        if ($bankId <= 0) {
            return $this->returnMsg(201, '', 'withdraw card required');
        }

        $dailyWithdrawTimes = (int) SystemConfig::getValue('daily_withdraw_times');
        if ($dailyWithdrawTimes > 0) {
            $todayCount = Withdraw::where('user_id', $user->id)->whereDate('created_at', date('Y-m-d'))->count();
            if ($todayCount >= $dailyWithdrawTimes) {
                return $this->returnMsg(216, '', 'daily withdraw limit reached');
            }
        }

        $date = date('Y-m-d');
        $withdrawBeginTime = SystemConfig::getValue('withdraw_begin_time');
        if ($withdrawBeginTime && time() < strtotime($date.' '.$withdrawBeginTime)) {
            return $this->returnMsg(218, '', 'withdraw time not started');
        }
        $withdrawEndTime = SystemConfig::getValue('withdraw_end_time');
        if ($withdrawEndTime && time() > strtotime($date.' '.$withdrawEndTime)) {
            return $this->returnMsg(219, '', 'withdraw time ended');
        }

        $lastApprovedWithdraw = Withdraw::where('user_id', $user->id)->where('state', 2)->orderBy('id', 'desc')->first();
        if ($lastApprovedWithdraw) {
            $rechargeAmount = Recharge::where('user_id', $user->id)->where('state', 2)->where('created_at', '>=', $lastApprovedWithdraw->created_at)->sum('amount');
            $betAmount = GameRecord::where('user_id', $user->id)->where('created_at', '>=', $lastApprovedWithdraw->created_at)->sum('valid_amount');
        } else {
            $rechargeAmount = Recharge::where('user_id', $user->id)->where('state', 2)->sum('amount');
            $betAmount = GameRecord::where('user_id', $user->id)->sum('valid_amount');
        }

        $turnoverRate = (float) SystemConfig::getValue('withdraw_fee');
        if ($turnoverRate > 0 && $rechargeAmount > 0 && ($betAmount / $rechargeAmount) < $turnoverRate) {
            return $this->returnMsg(214, '', 'turnover requirement not met');
        }

        $minWithdraw = (float) SystemConfig::getValue('min_withdraw_money');
        if ($minWithdraw > 0 && $amount < $minWithdraw) {
            return $this->returnMsg(201, '', 'min withdraw amount '.$minWithdraw);
        }
        $maxWithdraw = (float) SystemConfig::getValue('max_withdraw_money');
        if ($maxWithdraw > 0 && $amount > $maxWithdraw) {
            return $this->returnMsg(201, '', 'max withdraw amount '.$maxWithdraw);
        }

        $password = (string)($data['password'] ?? ($data['qk_pwd'] ?? ($data['paypwd'] ?? '')));
        if ($password === '') {
            return $this->returnMsg(520, '', 'withdraw password required');
        }
        if (empty($user->paypwd)) {
            return $this->returnMsg(520, '', 'withdraw password not set');
        }
        if (!Hash::check($password, $user->paypwd)) {
            return $this->returnMsg(520, '', 'withdraw password incorrect');
        }

        $tg = new TgService;
        $transferService = new SafeGameTransferService();
        $sweepResult = $transferService->moveLastPlatformBalanceToAccount($user, $tg, 'app_withdraw');
        if (($sweepResult['code'] ?? 201) != 200) {
            return $this->returnMsg($sweepResult['code'] ?? 201, $sweepResult, $sweepResult['message'] ?? 'transfer failed');
        }
        $user = User::where('id', $user->id)->first();
        if (!$user) {
            return $this->returnMsg(201, '', 'login expired');
        }
		
        $card = UserCard::where('id', $bankId)->where('user_id', $user->id)->first();
        if (!$card) {
            return $this->returnMsg(201, '', 'withdraw card not found');
        }

        $order_no = time().rand(1000,9999);
        $type = 1;
        $cashFee = 0;
        $realMoney = $amount;
        $usdtRate = (float) SystemConfig::getValue('withdraw_usdt_rate');
        if ($card['bank'] == 'USDT' && ($card['bank_address'] == 'TRC20' || $card['bank_owner'] == 'TRC20')) {
            if ($usdtRate <= 0) {
                return $this->returnMsg(500, '', 'system config missing');
            }
            $type = 2;
            $cashFee = (float) (SystemConfig::getValue('withdraw_cash_fee') ?? 0);
            $realMoney = round($amount / $usdtRate - $cashFee, 2);
        } elseif ($card['bank'] == 'USDT' && ($card['bank_address'] == 'ERC20' || $card['bank_owner'] == 'ERC20')) {
            if ($usdtRate <= 0) {
                return $this->returnMsg(500, '', 'system config missing');
            }
            $type = 3;
            $cashFee = (float) (SystemConfig::getValue('withdraw_fee_usdt_erc') ?? 0);
            $realMoney = round($amount / $usdtRate - $cashFee, 2);
        } elseif ($card['bank'] == 'ebpay') {
            $type = 4;
        }

        if ($realMoney <= 0) {
            return $this->returnMsg(214, '', 'withdraw fee exceeds amount');
        }

        $result = DB::transaction(function () use ($user, $amount, $card, $order_no, $type, $cashFee, $realMoney, $usdtRate) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return ['code' => 201, 'message' => 'login expired'];
            }
            if ($lockedUser->balance < $amount) {
                return ['code' => 201, 'message' => 'insufficient balance'];
            }

            $lockedUser->balance -= $amount;
            $lockedUser->save();
            $item = [
                'order_no' => $order_no,
                'type' => $type,
                'card_id' => $card->id,
                'user_id' => $lockedUser->id,
                'amount' => $amount,
                'cash_fee' => $cashFee,
                'real_money' => $realMoney,
                'usdt_rate' => ($type == 1) ? 0 : $usdtRate
            ];

            return [
                'code' => 200,
                'withdraw' => Withdraw::create($item),
            ];
        });

        if (($result['code'] ?? 500) !== 200) {
            return $this->returnMsg($result['code'], '', $result['message'] ?? '');
        }

        return $this->returnMsg(!empty($result['withdraw']) ? 200 : 500, '', !empty($result['withdraw']) ? 'withdraw submitted' : 'withdraw failed');
	}
    public function post_update_bank_info(Request $request)
    {
		$data = $request->all();
		if($data['bank_name'] == ''){
			return $this->returnMsg(201,'','请选择银行');
		}
		if($data['bank_address'] == ''){
			return $this->returnMsg(201,'','请输入开户地址');
		}
		if($data['bank_username'] == ''){
			return $this->returnMsg(201,'','请输入持卡人姓名');
		}
		if($data['bank_card'] == ''){
			return $this->returnMsg(201,'','请输入银行卡账号');
		}
		$user = User::where('api_token',$data['lastsession'])->first(); 
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}		
		$UserCard = UserCard::where('user_id',$user->id)->count();
		if($UserCard >= 5){
			return $this->returnMsg(201,'','每个会员最多可绑定5张卡');
		}
		if($data['bank_name'] == 98 || $data['bank_name'] == 99){
			$Bank['user_id'] = $user->id;
			$Bank['bank'] = 'USDT';
			$Bank['bank_no'] = $data['bank_card'];
			$Bank['bank_address'] = $data['bank_address'];
			$Bank['bank_owner'] = $data['bank_name'] == 98 ? 'ERC20' : 'TRC20';
			UserCard::create($Bank);
			return $this->returnMsg(200,$Bank,'成功');
		}
        $Bank = Bank::where('id',$data['bank_name'])->first();
        if(!$Bank){
			return $this->returnMsg(201,'','银行不存在');
		}	
		$Bankdata['user_id'] = $user->id;
		$Bankdata['bank'] = $Bank->bank_name;
		$Bankdata['bank_no'] = $data['bank_card'];
		$Bankdata['bank_address'] = $data['bank_address'];
		$Bankdata['bank_owner'] = $data['bank_username'];
		UserCard::create($Bankdata);		
		return $this->returnMsg(200,$Bankdata,'成功');
	}
	
    public function bindbanklist(Request $request)
    {
        $Bank = Bank::where('state',1)->select("id","bank_name")->orderBy('id','desc')->get()->toArray();
        
		$trc20 = array(
			    'id' => 99,
				'bank_name' => 'USDT-TRC20'
			);
		$erc20 = array(
			    'id' => 98,
				'bank_name' => 'USDT-ERC20'
			);			

		array_push($Bank,$trc20,$erc20);
		return $this->returnMsg(200,$Bank,'成功');
	}
	
    public function activitiesgo(Request $request)
    {
		$data = $request->all();
		$user = $this->activeUserFromApiToken($data['lastsession'] ?? '');
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}

        $activityId = (int)($data['activityid'] ?? ($data['activity_id'] ?? ($data['id'] ?? 0)));
        if ($activityId <= 0) {
            return $this->returnMsg(202, '', 'activity id required');
        }

		$activity = Activity::where('id', $activityId)->first();
		if(empty($activity)){
			return $this->returnMsg(202, '', '活动不存在');
		}
        if (empty((new PromotionService())->visible([$activity], 'mobile')) || (int)($activity->can_apply ?? 0) !== 1) {
            return $this->returnMsg(202, '', 'activity can not apply');
        }

		if ($hit = $this->activityBlacklistHit($user, $activityId)) {
			return $this->returnMsg(202, '', $this->activityBlacklistMessage($hit, 'activity can not apply'));
		}

        $couponCheck = $this->validateActivityCouponForApply($request, $user, $activityId);
        if (!$couponCheck['ok']) {
            return $this->returnMsg(202, '', $couponCheck['message']);
        }

		$isapple = ActivityApply::where("user_id",$user->id)->where('activity_id',$activityId)->first();
		if($isapple){
			if($isapple->state==1){
				return $this->returnMsg(202, '', '您已经申请过该活动，等待管理员审核');
			}
			if($isapple->state==2){
				return $this->returnMsg(202, '', '您已经申请过，已审核通过');
			}
			if($isapple->state==3){
				return $this->returnMsg(202, '', '您已经申请过，审核未通过');
			}
		}

		try {
            $created = DB::transaction(function () use ($activityId, $couponCheck, $user) {
                $created = ActivityApply::create($this->activityApplyPayload($activityId, $user, $couponCheck));
                if (!$this->markActivityCouponUsed($couponCheck['coupon'], $user)) {
                    throw new \RuntimeException('activity coupon consume failed');
                }

                return $created;
            });
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'Duplicate') !== false || stripos($e->getMessage(), 'activity_apply_activity_id_user_id_unique') !== false) {
                return $this->returnMsg(202, '', 'activity application already submitted');
            }

            \Illuminate\Support\Facades\Log::error('app activity apply failed', [
                'user_id' => $user->id,
                'activity_id' => $activityId,
                'message' => $e->getMessage(),
            ]);

            return $this->returnMsg(500, '', 'activity apply failed');
        }

		if($created){
			return $this->returnMsg(200, '', '申请成功');
		}else{
			return $this->returnMsg(202, '', '申请失败');
		}		
	}	
    public function activities(Request $request)
    {
        $Activity = [];
        $locale = $this->promotionLocaleFromRequest($request);
        $visible = (new PromotionService())->visible(Activity::with('type_data')->get()->all(), 'mobile');
		foreach($visible as $activity){
            $Activity[] = $this->appPromotionPayload($activity, $locale);
		}

		return $this->returnMsg(200,$Activity,'成功');
	}	

    private function appPromotionPayload(Activity $activity, $locale = 'zh')
    {
        $banner = ($activity->app_img ?? '') ?: ($activity->banner ?? '');
        $detailImage = ($activity->app_detail_image ?? '') ?: ($activity->detail_image ?? '') ?: $banner;
        $popupImage = ($activity->app_popup_image ?? '') ?: ($activity->popup_image ?? '') ?: $banner;
        $title = $this->promotionDisplayText($activity->title ?? '', $activity->entitle ?? '', $locale);
        $content = $this->promotionDisplayText($activity->content ?? '', $activity->encontent ?? '', $locale);
        $memo = $this->promotionDisplayText($activity->memo ?? '', $activity->enmemo ?? '', $locale);
        $typeName = $this->activityTypePublicName($activity->type_data, $locale);

        return [
            'id' => (int)($activity->id ?? 0),
            'type' => $this->legacyAppActivityType((int)($activity->type ?? 0)),
            'activity_type' => (int)($activity->type ?? 0),
            'type_name' => $typeName,
            'title' => $title,
            'entitle' => (string)($activity->entitle ?? ''),
            'content' => $content,
            'memo' => $memo,
            'enmemo' => (string)($activity->enmemo ?? ''),
            'app_img' => $this->formatAppUploadUrl($banner),
            'banner' => $this->formatAppUploadUrl($activity->banner ?? ''),
            'popup_image' => $this->formatAppUploadUrl($popupImage),
            'app_popup_image' => $this->formatAppUploadUrl($activity->app_popup_image ?? ''),
            'detail_image' => $this->formatAppUploadUrl($detailImage),
            'app_detail_image' => $this->formatAppUploadUrl($activity->app_detail_image ?? ''),
            'button_text' => $this->appPromotionButtonText($activity, $locale),
            'can_apply' => (int)($activity->can_apply ?? 0),
            'requires_auth' => (int)($activity->requires_auth ?? 0),
            'action_url' => (string)($activity->action_url ?? ''),
            'is_popup' => (int)($activity->is_popup ?? 0),
            'popup_frequency' => (string)(($activity->popup_frequency ?? '') ?: 'once'),
            'popup_delay_seconds' => (int)($activity->popup_delay_seconds ?? 0),
            'sort_order' => (int)($activity->sort_order ?? 0),
            'starts_at' => isset($activity->starts_at) ? (string)$activity->starts_at : '',
            'ends_at' => isset($activity->ends_at) ? (string)$activity->ends_at : '',
        ];
    }

    private function legacyAppActivityType($type)
    {
        $map = [
            5 => 6,
            6 => 3,
            7 => 4,
            8 => 1,
            10 => 5,
            11 => 2,
            12 => 7,
        ];

        return $map[$type] ?? 99;
    }

    private function appPromotionButtonText(Activity $activity, $locale = 'zh')
    {
        $configured = trim((string)($activity->button_text ?? ''));
        if ($configured !== '' && !$this->promotionHasBrokenText($configured) && ($locale === 'th' || !$this->promotionHasThaiText($configured))) {
            return $configured;
        }

        $url = trim((string)($activity->action_url ?? ''));
        if ($locale !== 'th') {
            if ($url !== '') {
                if (stripos($url, 'recharge') !== false || stripos($url, 'deposit') !== false) {
                    return '立即充值';
                }
                if (stripos($url, 'support') !== false || stripos($url, 'service') !== false) {
                    return '联系客服';
                }

                return '查看详情';
            }

            return (int)($activity->can_apply ?? 0) === 1 ? '申请活动' : '查看详情';
        }

        if ($url !== '') {
            if (stripos($url, 'recharge') !== false || stripos($url, 'deposit') !== false) {
                return 'เติมเงินทันที';
            }
            if (stripos($url, 'support') !== false || stripos($url, 'service') !== false) {
                return 'ติดต่อฝ่ายบริการ';
            }

            return 'ดูรายละเอียด';
        }

        return (int)($activity->can_apply ?? 0) === 1 ? 'รับโปรโมชั่น' : 'ดูรายละเอียด';
    }

    private function promotionDisplayText($primary, $fallback, $locale = 'zh')
    {
        if ($locale === 'th') {
            $translated = trim((string)$fallback);
            if ($translated !== '' && !$this->promotionHasBrokenText($translated)) {
                return $translated;
            }
        }

        $primary = trim((string)$primary);
        if ($primary !== '' && !$this->promotionHasBrokenText($primary)) {
            return $primary;
        }

        $fallback = trim((string)$fallback);
        return $this->promotionHasBrokenText($fallback) ? '' : $fallback;
    }

    private function activityTypePublicName($type, $locale = 'zh')
    {
        if (!$type) {
            return '';
        }

        return $this->promotionDisplayText($type->name ?? '', $type->enname ?? '', $locale);
    }

    private function promotionLocaleFromRequest(Request $request)
    {
        $locale = (string) (
            $request->input('locale')
            ?: $request->input('language')
            ?: $request->input('lang')
            ?: $request->header('Lang')
            ?: $request->header('Accept-Language')
            ?: 'zh-CN'
        );
        $locale = strtolower(str_replace('_', '-', trim($locale)));

        return strpos($locale, 'th') === 0 ? 'th' : 'zh';
    }

    private function promotionHasThaiText($value)
    {
        return preg_match('/[\x{0E00}-\x{0E7F}]/u', (string) $value) === 1;
    }

    private function promotionHasBrokenText($value)
    {
        $text = (string) $value;
        if ($text === '') {
            return false;
        }
        if (preg_match('/[\x{F000}-\x{F8FF}\x{FFFD}]/u', $text) === 1) {
            return true;
        }

        foreach ([
            "\u{5599}\u{20AC}",
            "\u{5594}\u{66D5}",
            "\u{5594}\u{65B7}",
            "\u{5594}\u{FF40}",
            "\u{9435}",
            "\u{93BA}",
            "\u{942A}",
            "\u{947F}\u{6EDD}",
            "\u{95C1}\u{517C}",
            "\u{943E}",
            "\u{9395}",
        ] as $token) {
            if (strpos($text, $token) !== false) {
                return true;
            }
        }

        return false;
    }

    private function formatAppUploadUrl($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        if (strpos($path, '/assets/') === 0) {
            return rtrim(env('APP_URL'), '/') . $path;
        }

        return rtrim(env('APP_URL'), '/') . '/uploads/' . ltrim($path, '/');
    }

	//游戏类型：1真人,2捕鱼,3电子,4彩票,5体育,6棋牌,7电竞
    public function usergameForm(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
        $game_type = $data['gameType'];
		$timeType = $data['timeType'];
		$time = time();
		$start_at = date('Y-m-d 00:00:00');
		$end_at = date('Y-m-d H:i:s');
		if($timeType == 2){
			$start_at = date('Y-m-d 00:00:00',$time - 86400);
			$end_at = date('Y-m-d 23:59:59',$time - 86400);
		}
		if($timeType == 3){
			$start_at = date('Y-m-01 00:00:00');
			$end_at = date('Y-m-d H:i:s');
		}		
		$where = array();
		$where['user_id'] = $user->id;
		$where['game_type'] = $game_type;
		$where['status'] = 1;
		$Game_Record = GameRecord::where($where)->whereBetween('created_at', [$start_at, $end_at])->first( array( \DB::raw('SUM(valid_amount) as valid_amount'), \DB::raw('SUM(win_loss) as win_loss') ) )->toArray();
        return response()->json([
            'code'    => 200,
            'message' => '成功',
            'data'    => '',
			'total_betAmount' => $Game_Record['valid_amount'] ? $Game_Record['valid_amount'] : '0.00',
			'total_validBetAmount' => $Game_Record['win_loss'] ? $Game_Record['win_loss'] : '0.00'
        ]);
	}	
    public function gameRecordList(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
        $page = $data['page'];
        $start_at = date('Y-m-d H:i:s', time() - 60 * 1440 * 30);
		$end_at = date('Y-m-d H:i:s');
		$Game_Record = GameRecord::where("user_id",$user->id)->select("platform_type as api_name","game_code as gameCode","bet_amount as betAmount","win_loss as netAmount","bet_time as betTime")->whereBetween('created_at', [$start_at, $end_at])->orderBy('id', 'desc')->paginate(20,['*'],'page',$page)->toArray();
		return $this->returnMsg(200,$Game_Record,'成功');
	}	
    public function register(Request $request)
    {
        $rules = [
    		'name' => 'required',
            'password' => 'required|min:6|max:12',
			'qk_pwd' => 'required|min:6|max:12',
			'realname' => 'required',
    	];
        $this->validate($request,$rules,$this->messages);
		$data = $request->all();
        $user = User::where('username',$data['name'])->first();
        if ($user) return $this->returnMsg(202,'','会员已存在');
        $arr = [
            'username' => $data['name'],
            'realname' => $data['realname'],
            'password' => Hash::make($data['password']),
            'paypwd' =>Hash::make($data['qk_pwd']),
            'status' => 1,
            'vip' => 1,
            'api_token' => Str::random(60),
            'pid' => $this->resolveInvitePid($data['pid'] ?? 0)
        ];
        $res = User::create($arr);
		return $this->returnMsg($res ? 200 : 500,'','成功');
	}	

    private function resolveInvitePid($inviteCode): int
    {
        $inviteCode = trim((string) $inviteCode);
        if ($inviteCode === '' || $inviteCode === '0') {
            return 0;
        }

        if (ctype_digit($inviteCode)) {
            $userId = (int) User::where('id', (int) $inviteCode)->value('id');
            if ($userId > 0) {
                return $userId;
            }
        }

        return (int) User::where('username', $inviteCode)->value('id');
    }

    public function islogin(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}
		$UserCard = UserCard::where('user_id',$user->id)->select("id","bank as bank_name","bank_no as bank_card","bank_address","bank_owner as bank_username")->orderBy('id','desc')->get()->toArray();
		$userdata = array();
		$userdata['last_session'] = $user->api_token;  //会员登陆token
		$userdata['id'] = $user->id;   //会员id
		$userdata['agent'] = $user->isagent;   //是否代理
		$userdata['name'] = $user->username;  //会员账号
		$userdata['money'] = $user->balance;  //会员余额
		$userdata['boxmoney'] = 0;  //会员保险箱余额
		$userdata['invite_code'] = $user->id;  //会员推广码
		$userdata['real_name'] = $user->realname;  //会员真实姓名
		$userdata['phone'] = $user->phone;  //会员手机
		$userdata['email'] = $user->mail;  //会员邮箱
		$userdata['qq'] = '';  //会员QQ
		$userdata['weixin'] = '';  //会员微信
		$userdata['user_bank'] = $UserCard;  //会员微信
		/*$userdata['bank_username'] = '姓名';  //会员银行卡姓名
		$userdata['bank_name'] = '遵义市商业银行';  //会员银行名称
		$userdata['bank_address'] = '开户地址';  //会员银行开户地址
		$userdata['bank_card'] = '123456789';  //会员银行卡号*/
		
		return $this->returnMsg(200,$userdata,'成功');		
	}
    public function getMoney(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}		
		$TransferLog = TransferLog::where('transfer_type', 0)->where('user_id', $user->id)->orderBy('id', 'desc')->first();
        if($TransferLog && $TransferLog->api_type != ''){	
		    $tg = new TgService; 
			$result = $tg->balance($TransferLog->api_type,$user->username);
			if($result['code'] != 200){
				return $this->returnMsg(201,'',$result['message']);	
			}
			$api_money = $result['data'];
		}			
		$money = $user->balance + $api_money;
		return $this->returnMsg(200,$money,'成功');		
	}
    public function userChildren(Request $request)
    {
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}		
		$where = array();
		if($request->has('name')){
			$where['username'] = $data['name'];
		}
		$where['pid'] = $user->id;
	    $user_list = User::where($where)->orderBy('id','desc')->get()->toArray();
		$zhishu_xinzeng = User::where('pid',$user->id)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->count();
		$ziying_yeji = GameRecord::where("user_id", $user->id)->where("status", 1)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->sum('win_loss');
		$wode_zonge = GameRecord::where("user_id", $user->id)->where("status", 1)->whereBetween('created_at', [date('Y-m-d H:i:s',time() - 60 * 1440 * 30), date('Y-m-d H:i:s',time())])->sum('bet_amount');
		$weilingfanshui = TransferLog::where("user_id", $user->id)->where("transfer_type", 6)->where("state", 0)->whereBetween('created_at', [date('Y-m-d H:i:s',time() - 60 * 1440 * 30), date('Y-m-d H:i:s',time())])->sum('money');
		$lingqufanshui = TransferLog::where("user_id", $user->id)->where("transfer_type", 6)->where("state", 1)->whereBetween('created_at', [date('Y-m-d H:i:s',time() - 60 * 1440 * 30), date('Y-m-d H:i:s',time())])->sum('money');
		$zhishu_liushui_total = 0;
        $zhishu_yeji = 0;
		foreach($user_list as $key => $value){
			$gmaefecord = GameRecord::where("user_id", $value['id'])->where("status", 1)->whereBetween('created_at', [date('Y-m-d H:i:s',time() - 60 * 1440 * 30), date('Y-m-d H:i:s',time())])->sum('bet_amount');
			$user_data = User::where('pid',$value['id'])->count();
			$user_list[$key]['total_amount'] = $gmaefecord;
			$user_list[$key]['zhishu'] = $user_data;
			$zhishu_liushui_total+=$gmaefecord;
			
			$zhishu_touzhu = GameRecord::where("user_id", $value['id'])->where("status", 1)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->sum('win_loss');
			$zhishu_yeji += $zhishu_touzhu;
		}
        return response()->json([
            'code'    => 200,
            'message' => '成功',
            'data'    => $user_list,
			'zhishu_total' => count($user_list),  //直属人数
			'zhishu_liushui_total' => $zhishu_liushui_total,  //直属总流水
			'zhishu_xinzeng' => $zhishu_xinzeng, //直属新增
			'zhishu_yeji' => $zhishu_yeji,  //今日直属业绩
			'ziying_yeji' => $ziying_yeji, //今日自营业绩
			'wode_zonge' => $wode_zonge, //我的投注总额
			'weilingfanshui' => $weilingfanshui, //未领取反水
			'lingqufanshui' => $lingqufanshui //已领反水
        ]);		
	}

	public function Regurgitation(Request $request){
        $data = $request->all();		
        $user = User::where('api_token',$data['lastsession'])->first();
        if (!$user) return $this->returnMsg(202,'','会员不存在或登陆信息已过期');		
        $TransferLog = TransferLog::where("user_id", $user->id)->where("transfer_type", 6)->where("state", 0)->whereBetween('created_at', [date('Y-m-d H:i:s',time() - 60 * 1440 * 30), date('Y-m-d H:i:s',time())])->get()->toArray();
        $money = 0;
		foreach($TransferLog as $key => $value){
			$TransferLogs = TransferLog::where("id", $value['id'])->first();
			$TransferLogs->state = 1;
			$TransferLogs->save();
			$money += $value['money'];
		}
		if($money > 0){
			$user->increment('balance', $money);
			return $this->returnMsg(200,'','操作成功');
		}
		return $this->returnMsg(201,'','没有可领取的反水');
	}
	public function GetTeamMember($members, $mid) {
		$Teams=array();//最终结果
		$mids=array($mid);//第一次执行时候的用户id
		do {
			$othermids=array();
			$state=false;
			foreach ($mids as $valueone) {
				foreach ($members as $key => $valuetwo) {
					if($valuetwo['pid']==$valueone){
						$Teams[]=$valuetwo['id'];//找到我的下级立即添加到最终结果中
						$othermids[]=$valuetwo['id'];//将我的下级id保存起来用来下轮循环他的下级
						array_splice($members,$key,1);//从所有会员中删除他
						$state=true;   
					}
				}          
			}
			$mids=$othermids;//foreach中找到的我的下级集合,用来下次循环
		} while ($state==true);
	 
		return count($Teams);
	}	
    public function service_center(Request $request)
    {
        $notice = Article::where('cateid',6)->limit(3)->select("name as title","content","created_at as time")->orderBy('stor','asc')->get()->toArray();
		foreach($notice as $k => $v){
			$notice[$k]['content'] = strip_tags($v['content']);
		}
		return $this->returnMsg(200,$notice,'成功');		
	}
    public function systeminfo(Request $request)
    {
		$data = array();
		if($request->has('lastsession')){
			$user = $this->activeUserFromApiToken($request->lastsession);
            if ($user) {
                $wapurl = env("WAP_URL");
    			$wapurl = explode(',', $wapurl);	
    			$register_url = $wapurl[0].'/#/register?pid='.$user->id;

    			$qrcodes = public_path('/qrcodes/');
    			// qrcodes 目录不存在，则创建文件夹
    			File::isDirectory($qrcodes) or File::makeDirectory($qrcodes, 0777, true, true);
    			
    			$img_file = 'qrcodes/'.$user['username'].'.png';
    			if(!file_exists($img_file)){
    				$QrCode = QrCode::format('png')->size(300)->generate($register_url,public_path($img_file));
    			}			
    			$data['qrcode'] = env('APP_URL').'/'.$img_file;
    			
    			
    			$public_path = public_path('/inviterQrcodes/');
    			// $public_path 目录不存在，则创建文件夹
    			File::isDirectory($public_path) or File::makeDirectory($public_path, 0777, true, true);
                $inviterQrcodes = 'inviterQrcodes/'.$user['username'].'.png';
    			if(!file_exists($inviterQrcodes)){
    				$bg = imagecreatefrompng(public_path('/src_761chess_resource_img_extension_shareqrbg.png'));// 提前准备好的海报图
    				$qrcode = imagecreatefrompng(public_path($img_file));
    				imagecopyresampled($bg, $qrcode, 105, 365, 0, 0, 70, 70, imagesx($qrcode), imagesy($qrcode));
    				imagepng($bg, public_path('/inviterQrcodes/' . $user['username'].'.png'));
    			}			

    			$data['inviterQrcodes'] = env('APP_URL').'/'.$inviterQrcodes;
            }
		}
		$data = array_merge($data, $this->customerServicePayload($this->requestPlayerLevel($request)));
		$data['qq'] = '';
		$data['wx'] = '';
		return $this->returnMsg(200,$data,'成功');		
	}


    public function querys(Request $request)
    {
		$notice = Article::where('name','like','%常见问题%')->first();
		return $this->returnMsg(200,$notice->content,'成功');		
	}	
    public function login(Request $request)
    {

        $rules = [
    		'name' => 'required',
            'password' => 'required|min:6',
    	];
        $this->validate($request,$rules,$this->messages);
        $data = $request->all();		
        $user = User::where('username',$data['name'])->first();
        if (!$user) return $this->returnMsg(202,'','会员不存在');
        if (Hash::check($data['password'],$user->password)) {
            $api_token = Str::random(60);
            $postdata['lastip'] = $request->getClientIp();
            $postdata['logintime'] = time();
            $postdata['loginsum'] =  $user->loginsum++;
            $postdata['api_token'] = $api_token;

            if(User::where('username',$data['name'])->update($postdata)){
				$UserCard = UserCard::where('user_id',$user->id)->select("id","bank as bank_name","bank_no as bank_card","bank_address","bank_owner as bank_username")->orderBy('id','desc')->get()->toArray();
                $userdata = array();
				$userdata['last_session'] = $api_token;  //会员登陆token
				$userdata['id'] = $user->id;   //会员id
				$userdata['agent'] = $user->isagent;   //是否代理
				$userdata['name'] = $user->username;  //会员账号
				$userdata['money'] = $user->balance;  //会员余额
				$userdata['boxmoney'] = 0;  //会员保险箱余额
				$userdata['invite_code'] = '';  //会员推广码
				$userdata['real_name'] = $user->realname;  //会员真实姓名
				$userdata['phone'] = $user->phone;  //会员手机
				$userdata['email'] = $user->mail;  //会员邮箱
				$userdata['qq'] = '';  //会员QQ
				$userdata['weixin'] = '';  //会员微信
				$userdata['user_bank'] = $UserCard;  //会员银行卡信息
				/*$userdata['bank_username'] = '';  //会员银行卡姓名
				$userdata['bank_name'] = '';  //会员银行名称
				$userdata['bank_address'] = '';  //会员银行开户地址
				$userdata['bank_card'] = '';  //会员银行卡号*/
                return $this->returnMsg(200,$userdata,'成功');
            }         
            return $this->returnMsg(203,'','登陆失败,请联系客服');
        } else {
            return $this->returnMsg(203,'','密码错误');
        }		
    }	
    public function hall_list(Request $request)
    {
		//游戏类型：1真人,2捕鱼,3电子,4彩票,5体育,6棋牌,7电竞		
		$list = GameList::where('is_top',1)->where('site_state',1)->where('app_state',1)->select('name','platform_name as Code','category_id as GameType','game_code as GameCode','app_img')->orderBy('order_by','asc')->get()->toArray();
        foreach($list as $k => $v){
			if($v['GameType'] == 'realbet'){
				$list[$k]['GameType'] = 1;
			}
			if($v['GameType'] == 'sport'){
				$list[$k]['GameType'] = 5;
			}
			if($v['GameType'] == 'gaming'){
				$list[$k]['GameType'] = 7;
			}
			if($v['GameType'] == 'joker'){
				$list[$k]['GameType'] = 6;
			}
			if($v['GameType'] == 'lottery'){
				$list[$k]['GameType'] = 4;
			}
			if($v['GameType'] == 'concise'){
				$list[$k]['GameType'] = 3;
			}
			if($v['GameType'] == 'fishing'){
				$list[$k]['GameType'] = 2;
			}			
			$list[$k]['app_img'] = env('APP_URL').'/uploads/'.$v['app_img'];
		}
		$hot = GameListApp::where('app_state',1)->select('name','platform_name as Code','category_id as GameType','game_code as GameCode','app_img')->orderBy('order_by','asc')->get()->toArray();
        foreach($hot as $k => $v){
			if($v['GameType'] == 'realbet'){
				$hot[$k]['GameType'] = 1;
			}
			if($v['GameType'] == 'sport'){
				$hot[$k]['GameType'] = 5;
			}
			if($v['GameType'] == 'gaming'){
				$hot[$k]['GameType'] = 7;
			}			
			if($v['GameType'] == 'joker'){
				$hot[$k]['GameType'] = 6;
			}
			if($v['GameType'] == 'lottery'){
				$hot[$k]['GameType'] = 4;
			}
			if($v['GameType'] == 'concise'){
				$hot[$k]['GameType'] = 3;
			}
			if($v['GameType'] == 'fishing'){
				$hot[$k]['GameType'] = 2;
			}			
			$hot[$k]['app_img'] = env('APP_URL').'/uploads/'.$v['app_img'];
		}
        $data['list'] = $list;
        $data['hot'] = $hot;		
	    return $this->returnMsg(200,$data,'成功');
	}
    public function update_password(Request $request)
    {
        $rules = [
            'password' => 'required|min:6',
    	];
        $this->validate($request,$rules,$this->messages);		
		$data = $request->all();
        $old_password = $data['old_password'];
        $password = $data['password'];
        $lastsession = $data['lastsession'];
		$type = $data['type'];
        $user = User::where('api_token',$lastsession)->lockForUpdate()->first();
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}		
		if (!Hash::check($old_password,$user->password)) return $this->returnMsg(205,[],'原密码错误');
        $user->password = Hash::make($password);
        $user->save();
        if($user->save()){
            return $this->returnMsg(200);
        }else{
            return $this->returnMsg(300,[],'修改失败');
        }		
	}	
    public function api_login(Request $request)
    {
		$data = $request->all();
        $api_code = strtoupper(trim((string)($data['Code'] ?? '')));
        $gameCode = trim((string)($data['GameCode'] ?? ''));
        $gameType = trim((string)($data['GameType'] ?? ''));
		$lastsession = $data['lastsession'] ?? '';
        $is_mobile_url = 1;

        $normalizedGame = $this->normalizeAppGameRequest($gameType, $gameCode);
        $gameType = $normalizedGame['game_type'];
        $gameCode = $normalizedGame['game_code'];
        $leixing = $this->appGameCategoryMap()[$gameType] ?? (preg_match('/^[1-7]$/', $gameType) ? $gameType : '1');

        if (!$this->isAppApiPlayable($api_code)) {
            return $this->returnMsg(500, [], 'game platform is closed');
        }

        if (!$this->isAppGamePlayable($api_code, $gameType, $gameCode)) {
            return $this->returnMsg(500, [], 'game is closed or missing');
        }

        $tg = new TgService;                
        $user = $this->activeUserFromApiToken($lastsession, true);
		if(!$user){
			return $this->returnMsg(201,'','登陆信息已过期');
		}

        if ($hit = $this->gameRestrictionHit($user, $api_code, $gameType, $gameCode)) {
            return $this->returnMsg(500, [], $this->tcgRestrictionMessage($hit, 'game access restricted'));
        }

		$User_Api = User_Api::where('api_code',$api_code)->where('user_id',$user->id)->first();
		if(!$User_Api){
			$result = $tg->register($api_code,$user->username);
            if($result['code'] != 200){
				return $this->returnMsg(201, '', $result['message']);
			}
			$arr = [
				'user_id' => $user->id,
				'api_user' => $user->username,
				'api_pass' => 123456,
				'api_code' => $api_code,
			];
			$User_Api = User_Api::create($arr);		    
		}
	
        if($user->transferstatus == 1){
			$mz = $this->allmz($api_code,$user->id);
			if($mz['code'] != 200){
				return $this->returnMsg(500,[],$mz['message']);				
			}
		}		
        $res = $tg->login($user->username, $api_code, $leixing, $is_mobile_url, $gameCode);
        
        if ($res['code'] == 200) {
            return $this->returnMsg(200, ['url' => $res['data']]);
        } else {
            return $this->returnMsg(500,$res,$res['message']);
        }		
	}
    public function allmz($plat_name,$userid){
        $user = User::where('id',$userid)->first();
        if (!$user) {
            return ['code' => 201, 'message' => '登陆信息已过期'];
        }

        $transferService = new SafeGameTransferService();
        return $transferService->autoMoveToPlatform($user, $plat_name, new TgService);
	} 
    protected function appGameCategoryMap()
    {
        return [
            'realbet' => '1',
            'fishing' => '2',
            'concise' => '3',
            'lottery' => '4',
            'lhc' => '4',
            'jsc' => '4',
            'jwc' => '4',
            'qkc' => '4',
            'sport' => '5',
            'joker' => '6',
            'gaming' => '7',
        ];
    }

    protected function normalizeAppGameRequest($rawGameType, $rawGameCode)
    {
        $categoryMap = $this->appGameCategoryMap();
        if ($this->isAppGameCategory($rawGameType, $categoryMap)) {
            $gameType = strtolower(trim((string) $rawGameType));
            $gameCode = $rawGameCode;
        } else {
            $gameType = strtolower(trim((string) $rawGameCode));
            $gameCode = $rawGameType;
        }

        $gameCode = $this->normalizeAppGameCode($gameCode);
        if ($gameCode === '') {
            $gameCode = '0';
        }

        return [
            'game_type' => $gameType,
            'game_code' => $gameCode,
        ];
    }

    protected function isAppGameCategory($value, array $categoryMap)
    {
        $value = strtolower(trim((string) $value));
        return isset($categoryMap[$value]) || preg_match('/^[1-7]$/', $value);
    }

    protected function isAppApiPlayable($apiCode)
    {
        return Api::whereRaw('UPPER(api_code) = ?', [strtoupper(trim((string) $apiCode))])
            ->where('state', 1)
            ->where('app_state', 1)
            ->exists();
    }

    protected function isAppGamePlayable($apiCode, $gameType, $gameCode)
    {
        $gameCode = $this->normalizeAppGameCode($gameCode);
        $categories = $this->localAppCategoriesForGameType($gameType);
        if (!$categories) {
            return false;
        }

        $query = GameList::whereRaw('UPPER(platform_name) = ?', [strtoupper(trim((string) $apiCode))])
            ->whereIn('category_id', $categories)
            ->where('site_state', 1)
            ->where('app_state', 1)
            ->where('is_top', 1);

        $gameCodeKey = strtolower($gameCode);
        if (!in_array($gameCodeKey, ['0', 'lobby'], true)) {
            $query->where('game_code', $gameCode);
        } else {
            $query->where(function ($query) {
                $query->where('game_code', '0')
                    ->orWhere('game_code', 'lobby')
                    ->orWhereNull('game_code')
                    ->orWhere('game_code', '');
            });
        }

        return $query->exists();
    }

    protected function localAppCategoriesForGameType($gameType)
    {
        $gameType = strtolower(trim((string) $gameType));
        $numberToCategory = [
            '1' => 'realbet',
            '2' => 'fishing',
            '3' => 'concise',
            '4' => 'lottery',
            '5' => 'sport',
            '6' => 'joker',
            '7' => 'gaming',
        ];
        $category = $numberToCategory[$gameType] ?? $gameType;

        if (in_array($category, ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'], true)) {
            return ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'];
        }

        if (!isset($this->appGameCategoryMap()[$category])) {
            return [];
        }

        return [$category];
    }

    protected function normalizeAppGameCode($value)
    {
        return trim((string) $value, " \t\n\r\0\x0B'\"");
    }

    public function write_log($data,$filepath=''){
        $data = is_array($data) ? json_encode($data) : $data;
        $data = date('Y-m-d H:i:s') . '   ' . $data;

        $filepath = $filepath ? $filepath : './app_log.txt';
        if($rsp = fopen($filepath, "a+b")) {
            fwrite($rsp, $data);
            fwrite($rsp, PHP_EOL."--------------------".PHP_EOL);
            fclose($rsp);
        }
    }
	public function float_number($num){
		if ($num >= 100000000) {
			$num = round($num / 100000000, 2) . '亿+';
		} else if ($num >= 10000000) {
			$num = round($num / 10000000, 3) . '千万';
		} else if ($num >= 10000) {
			$num = round($num / 10000, 2) . '万';
		}
		return $num;
	}
    public function http_post_json($url, $jsonStr)
    {		
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT,5);        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }	
}
