<?php
//decode by http://www.yunlu99.com/
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AgentSettlement;
use App\Models\GameRecord;
use App\Models\SystemConfig;
use App\Models\TransferLog;
use App\Models\Userredpacket;
use App\Models\Users;
use App\Models\UserVip;
use App\Models\Usersmoney;
use App\Services\GamereportService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\TgService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class AuthController extends Controller
{
    protected $messages = [
        'password.required' => '密码不能为空',
        'password.min' => '密码8到20位',
        'paypassword.min' => '密码8到20位',
        'paypassword.required' => '密码8到20位',
        'name.required' => '用户名不能为空',
        'realname.required' => '真实姓名不能为空',
        'password.confirmed' => '密码与确认密码不一致'
    ];

    /**
     * 注册
     *
     * @param Request $request
     * @return void
     */
    public function register(Request $request)
    {
        $rules = [
    		'name' => 'required',
    		'realname'=>'required',
            'password' => 'required|min:8',
            'paypassword' => 'required|min:8',
    	];
    	$this->validate($request,$rules,$this->messages);
        $data = $request->all();
        $user = User::where('username',$data['name'])->first();
        if ($user) return $this->returnMsg(201);

        $arr = [
            'username' => $data['name'],
            'realname' => $data['realname'],
            'password' => Hash::make($data['password']),
            'paypwd' =>Hash::make($data['paypassword']),
            'status' => 1,
            'vip' => 1,
            'api_token' => Str::random(60),
            'pid' => $this->resolveInvitePid($data['pid'] ?? 0)
        ];
        $res = User::create($arr);
        return $this->returnMsg($res ? 200 : 500,$res ?? []);
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

    /**
     * 登录
     *
     * @param Request $request
     * @return void
     */
    public function login(Request $request)
    {
        $rules = [
    		'name' => 'required',
            'password' => 'required|min:6',
    	];
        $this->validate($request,$rules,$this->messages);

        $data = $request->all();
        $user = User::where('username',$data['name'])->select('username','password','lastip','logintime','loginsum','api_token')->first();
        if (!$user) return $this->returnMsg(202);
        if (Hash::check($data['password'],$user->password)) {
            $api_token = Str::random(60);
            $postdata['lastip'] = $request->getClientIp();
            $postdata['logintime'] = time();
            $postdata['loginsum'] =  $user->loginsum++;
            if(empty($user->api_token)){
                $user->api_token = $api_token;
             $postdata['api_token'] = $api_token;
            }
            if(User::where('username',$data['name'])->update($postdata)){
               
                unset($user->password,$user->lastip,$user->loginsum,$user->logintime);
                return $this->returnMsg(200,$user);
            }
          
            return $this->returnMsg(203);
            //print_r($user);

        } else {
            return $this->returnMsg(203);
        }
    }
    
    public function login_pc(Request $request)
    {
        $rules = [
    		'name' => 'required',
            'password' => 'required|min:6',
    	];
        $this->validate($request,$rules,$this->messages);

        $data = $request->all();
        $user = User::where('username',$data['name'])->select('username','password','lastip','logintime','loginsum','api_token')->first();
        if (!$user) return $this->returnMsg(202);
        if (Hash::check($data['password'],$user->password)) {
            $api_token = Str::random(60);
            $postdata['lastip'] = $request->getClientIp();
            $postdata['logintime'] = time();
            $postdata['loginsum'] =  $user->loginsum++;
            // if(empty($user->api_token)){
                $user->api_token = $api_token;
             $postdata['api_token'] = $api_token;
            // }
            if(User::where('username',$data['name'])->update($postdata)){
               
                unset($user->password,$user->lastip,$user->loginsum,$user->logintime);
				$user->new_api_token = $api_token;
                return $this->returnMsg(200,$user);
            }
          
            return $this->returnMsg(203);
            //print_r($user);

        } else {
            return $this->returnMsg(203);
        }
    }


    /**
     * 读取账户余额
     *
     * @param Request $request
     * @return void
     */
    public function userblance(Request $request)
    {
        $data = $request->all();
        \Illuminate\Support\Facades\Log::info("演示站接受数据");
        \Illuminate\Support\Facades\Log::info($data);
        if(in_array($data['handle'],['getuserblance','upuserblance','creategamerecord','setgamerecord','updateUserTransAll','updategamerecord'])){

            if($data['handle']=="getuserblance"){
                $rules = [
                    'username' => 'required',
                    'merchantaccount'=>'required',
                    #'action'=>'required',
                    #'serialNumber'=>'required',
                    'sgin'=>'required',
                    'gametype'=>'required',
                ];
                $this->validate($request,$rules,$this->messages);
                $verdata = $this->encryptData($data);
                if(!$verdata){
                    return $this->returnMsg(202,'','验证错误');
                }
                $user = User::where('username',$data['username'])->first();
                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');
                return $this->returnMsg(200,array('balance'=>$user->balance));
            }elseif($data['handle']=="upuserblance"){
                $rules = [
                    'username' => 'required',
                    'merchantaccount'=>'required',
                    'sgin'=>'required',
                    'action'=>'required',
                    'amount'=>'required',
                    'serialNumber'=>'required',
                    'gametype'=>'required',
                ];
                $this->validate($request,$rules,$this->messages);
                $verdata = $this->encryptData($data);
                if(!$verdata){
                    return $this->returnMsg(202,'','验证错误');
                }
                $user = User::where('username',$data['username'])->first();
                if($data['type']==3){
                    $isuserorder = TransferLog::where('order_no',$data['serialNumber'])->first();
                    if(!$isuserorder){
                        return $this->returnMsg(200,array('balance'=>$user->balance));
                    }
                }else{
                    $islog = TransferLog::where('order_no',$data['serialNumber'])->first();
                    if ($islog) return $this->returnMsg(5008,'','订单已经存在');
                }

                $user = User::where('username',$data['username'])->first();

                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');
                if($data['action']==2){
                    if($user->balance<$data['amount']){
                        return $this->returnMsg(601,'','账户余额不足，无法完成');
                    }
                    $user->balance = $user->balance - $data['amount'] ;
                    $transfer_type = 0;
                    $amount = -$data['amount'];
                }
                if($data['action']==1){
                    $user->balance = $user->balance + $data['amount'] ;
                    $transfer_type = 1;
                    $amount = $data['amount'];
                }
                $user->save();

                $arr = [
                    'order_no' => $data['serialNumber'],
                    'api_type' => $data['gametype'],
                    'user_id' => $user->id,
                    'transfer_type' => $transfer_type,
                    'money' => $amount,
                    'cash_fee' => 0,
                    'real_money' =>$amount,
                    'before_money' => $user->balance + $amount,
                    'after_money' => $user->balance,
                    'state' => 1
                ];
                TransferLog::create($arr);

                return $this->returnMsg(200,array('balance'=>$user->balance));
            }elseif($data['handle']=="creategamerecord"){  //creategamerecord

                $user = User::where('username',$data['username'])->first();
                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');

                $arr['user_id'] = $user->id;
                $arr['game_type'] = $data['gametype'];
				$arr['game_code'] = $data['gamecode'];
                $arr['username'] =$data['username'];
                $arr['platform_type'] = $data['platform_type'];
                $arr['bet_amount'] =  $data['bet_amount'];
                $arr['valid_amount'] =  empty($data['valid_amount']) ? $data['bet_amount'] : $data['valid_amount'];
                $arr['win_loss'] =  (isset($data['win_loss'])) ?  $data['win_loss'] : 0;
                $arr['bet_id'] = $data['bet_id'];
                $arr['status'] =  $data['status'];
                $arr['bet_time'] = date('Y-m-d H:i:s',strtotime($data['bet_time']));
                $arr['is_back'] =0;
                $arr['created_at'] =date('Y-m-d H:i:s');
                $arr['updated_at'] =date('Y-m-d H:i:s');
                $user->totalgame += $data['valid_amount'];
                $user->save();

                GameRecord::create($arr);

                $min_fanshui_money = intval(SystemConfig::getValue('min_fanshui_money'));
                if(!self::checkTransferLog($user->id,$data['platform_type'],$data['bet_id'],6)) {
                    if ($data['valid_amount'] > $min_fanshui_money && $data['status'] == 1) { //会员返水
                        self::gameFanshui($user->id, $data['valid_amount'], $data['platform_type'],$data['bet_id'],$data['gametype']);
                    }
                }

                self::upuserlevel($user->id);  //会员升级
                if(!self::checkTransferLog($user->id,$data['platform_type'],$data['bet_id'],20)) {
                    $settlementtypes = intval(SystemConfig::getValue('settlementtypes'));  //结算方法，1输赢，0打码
                    $settlementlevel = (intval(SystemConfig::getValue('settlementlevel'))==0) ? 20 : intval(SystemConfig::getValue('settlementlevel'));//代理返佣级数
                    // if ($settlementtypes == 1 && $data['win_loss'] < 0 && $data['status'] == 1) {
                    //     $money = $data['win_loss'];
                    //     self::agentFanshui($user->id, $money, 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
                    // }
                    if ($settlementtypes == 1 && $data['status'] == 1) {
                        $money = -$data['win_loss'];
                        self::agentFanshui($user->id, $money, 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
                    }
                    if ($settlementtypes == 0 && $data['valid_amount'] > $min_fanshui_money && $data['status'] == 1) {
                        self::agentFanshui($user->id, $data['valid_amount'], 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
                    }
                }
            
            }elseif($data['handle']=="updategamerecord"){  //更新下注记录
                $user = User::where('username',$data['username'])->first();
                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');

                $gmaefecord = GameRecord::where("platform_type", $data['platform_type'])->where("username", $data['username'])->where("bet_id", $data['bet_id'])->first();
                if($gmaefecord){
                    $valid_amount = $gmaefecord->valid_amount;
                    $gmaefecord->valid_amount = $data['valid_amount'];
                    $gmaefecord->win_loss = $data['win_loss'];
					$gmaefecord->status = $data['status'];
                    $gmaefecord->save();

					$min_fanshui_money = intval(SystemConfig::getValue('min_fanshui_money'));
					if(!self::checkTransferLog($user->id,$data['platform_type'],$data['bet_id'],6)) {
						if ($data['valid_amount'] > $min_fanshui_money && $data['status'] == 1) { //会员返水
							self::gameFanshui($user->id, $data['valid_amount'], $data['platform_type'],$data['bet_id'],$data['gametype']);
						}
					}

					self::upuserlevel($user->id);  //会员升级
					if(!self::checkTransferLog($user->id,$data['platform_type'],$data['bet_id'],20)) {
						$settlementtypes = intval(SystemConfig::getValue('settlementtypes'));  //结算方法，1输赢，0打码
						$settlementlevel = (intval(SystemConfig::getValue('settlementlevel'))==0) ? 20 : intval(SystemConfig::getValue('settlementlevel'));//代理返佣级数
						if ($settlementtypes == 1 && $data['status'] == 1) {
							$money = -$data['win_loss'];
							self::agentFanshui($user->id, $money, 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
						}
						if ($settlementtypes == 0 && $data['valid_amount'] > $min_fanshui_money && $data['status'] == 1) {
							self::agentFanshui($user->id, $data['valid_amount'], 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
						}
					}
				
                    return $this->returnMsg(200,$valid_amount,'更新成功');
                }else{
                    return $this->returnMsg(202,'','没有查询到下注信息');
                }
            }elseif($data['handle']=="cancelgamerecord"){  //creategamerecord
                $verdata = $this->encryptData($data);
                if(!$verdata){
                    return $this->returnMsg(202,'','验证错误');
                }

                $user = User::where('username',$data['username'])->first();
                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');

                $gmaefecord = GameRecord::where("platform_type", $data['platform_type'])->where("username", $data['username'])->where("bet_id", $data['bet_id'])->first();
                if($gmaefecord){
                    $valid_amount = $gmaefecord->valid_amount;
                    $gmaefecord->status = 2 ;
                    $gmaefecord->save();
                    $user->balance += $valid_amount;
                    $user->save();
                    return $this->returnMsg(200,$valid_amount,'更新成功');
                }else{
                    return $this->returnMsg(202,'','没有查询到下注信息');
                }

            }elseif($data['handle']=="setgamerecord"){  //開獎操作
                $verdata = $this->encryptData($data);
                if(!$verdata){
                    return $this->returnMsg(202,'','验证错误');
                }
                $user = User::where('username',$data['username'])->first();
                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');

                $gmaefecord = GameRecord::where("platform_type", $data['platform_type'])->where("username", $data['username'])->where("bet_id", $data['bet_id'])->first();
                if($gmaefecord){
                    $gmaefecord->win_loss = $data['win_loss'];
                    $gmaefecord->status = 1 ;
                    $gmaefecord->save();
                    if(!self::checkTransferLog($user->id,$data['platform_type'],$data['bet_id'],6)){
                        self::gameFanshui($user->id,$gmaefecord['bet_amount'],$data['platform_type'],$data['bet_id'],1);
                    }

                    if(!self::checkTransferLog($user->id,$data['platform_type'],$data['bet_id'],20)) {
                        $min_fanshui_money = intval(SystemConfig::getValue('min_fanshui_money'));
                        $settlementtypes = intval(SystemConfig::getValue('settlementtypes'));
                        $settlementlevel = (intval(SystemConfig::getValue('settlementlevel'))==0) ? 20 : intval(SystemConfig::getValue('settlementlevel'));
                        // if ($settlementtypes == 1 && $data['win_loss'] < 0) {
                        //     $money = $data['win_loss'];
                        //     self::agentFanshui($user->id, $money, 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
                        // }
                        if ($settlementtypes == 1) {
                            $money = -$data['win_loss'];
                            self::agentFanshui($user->id, $money, 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
                        }
                        if ($settlementtypes == 0 && $gmaefecord['valid_amount'] > $min_fanshui_money && $gmaefecord['status'] == 1) {
                            self::agentFanshui($user->id, $gmaefecord['valid_amount'], 0, $data['platform_type'], $data['bet_id'],$settlementlevel,0);
                        }
                    }

                    return $this->returnMsg(200,$data['win_loss'],'更新成功');
                }else{
                    return $this->returnMsg(202,'','没有查询到下注信息');
                }

            }elseif($data['handle']=="updateUserTransAll"){  //一件提取钱包
/*                $verdata = $this->encryptData($data);
                if(!$verdata){
                    return $this->returnMsg(202,'','验证错误');
                }*/
                
                \Illuminate\Support\Facades\Log::info("一键回收结果服务器回调".$data['username']);
        
                \Illuminate\Support\Facades\Log::info(json_encode($data));                       
                
                $user = User::where('username',$data['username'])->first();
                if (!$user) return $this->returnMsg(202,'','获取用户信息错误');
                $tg = new TgService;
                
                $blance = 0;
                $arr=[];
                $userblance = json_decode($data['userblance'],true);
                foreach ($userblance as $val){
                    if($val['success']=="ok" && $val['blance']>0){
                        $client_transfer_id = time() . $user->id . rand(1000, 9999);
                        $arr[] = [
                            'order_no' => $client_transfer_id,
                            'api_type' => $val['gamecode'],
                            'user_id' => $user->id,
                            'transfer_type' => 1,
                            'money' => $val['blance'],
                            'cash_fee' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'real_money' => $val['blance'],
                            'before_money' => $user->balance+$blance ,
                            'after_money' => $user->balance +$blance +  $val['blance'],
                            'state' => 1
                        ];
                      
                         $blance +=$val['blance'];
                        // $this->AllAccounttranso($user,$val['gamecode'], $val['blance']);
                         //Usersmoney::kouinfo($this->id, $plat_name, $money);
                        
                          Usersmoney::setmoneyinit($user->id, $val['gamecode']);
                    }elseif($val['success']=="ok" && $val['blance']==0){
                          Usersmoney::setmoneyinit($user->id, $val['gamecode']);
                    }
                }
                if($blance>0){

                    $user->balance = $user->balance + $blance;
                    $user->save();                    
                    TransferLog::insert($arr);
                    return $this->returnMsg(200,'','共回收金额：'.$blance);    
                }else{
                    return $this->returnMsg(200,'','没有可回收的金额');    
                }            

            }
        }else{
            return $this->returnMsg(202,'','非常操作');
        }
    }



    /**
     * 一键回收
     * @return void
     */
    public function AllAccounttranso($user,$plat_name, $money)   // 游戏转账到余额
    {

        $user->balance = $user->balance + $money;
        $user->save();
        
        return array('code' => 200);
       
    }
    
    public function upuserlevel($uid){
        $userinfo = \App\Models\User::where("id",$uid)->first();
        $uservip = UserVip::where('status',1)->where('recharge','<=',$userinfo->paysum)->where('flow','<=',$userinfo->totalgame)->orderBy('id','desc')->first();
		if($uservip){
			$userinfo->vip = $uservip->id;
			$userinfo->save();			
		}

        // $uservip = UserVip::where("status",1)->orderBy("id","desc")->get();
        // foreach ($uservip as $val){
        //     if($userinfo->paysum>=$val->recharge && $userinfo->totalgame>=$val->flow && $userinfo->vip>$val->id){
        //         $userinfo->vip = $val->id;
        //         $userinfo->save();
        //         break;
        //     }
        // }
    }


    public function gameFanshui($uid,$money,$platform_type,$betid,$gametype)
    {        
        $userinfo =  \App\Models\User::where("id", $uid)->first();
        $uservip = UserVip::where("status", 1)->where("id", $userinfo->vip)->first();
        $fee = 0;

		switch ($gametype) {
			case '4':  //彩票
				$fee = $uservip->lottery;
				break;
			case '1':  //真人
				$fee = $uservip->realperson;
				break; 
			case '5':   //體育
				$fee = $uservip->sport;
				break;   
			case '7':   //電競
				$fee = $uservip->e_sport;
				break; 
			case '6':   //棋牌
				$fee = $uservip->joker;
				break; 
			case '3':  //電子
				$fee = $uservip->electron;
				break; 
			case '2':   //捕魚
				$fee = $uservip->fish;
				break;                         
			default:
				// code...
				break;
		}

        
       
        $money = round($fee * $money / 100, 2);
        if ($money > 0) {
            $arr = [
                'order_no' => date('Ymd') . '_' . $userinfo->id . '_' . time(),
                'api_type' => 'web',
                'user_id' => $userinfo->id,
                'transfer_type' => 6,
                'money' => $money,
                'cash_fee' => 0,
                'real_money' => $money,
                'before_money' => 0 ,
                'after_money' => 0,
                'state' => 0,
                'platform_type'=>$platform_type,
                'betid'=>$betid
            ];
            TransferLog::create($arr);
        }

    }

    public function checkTransferLog($uid,$platform_type,$betid,$transfer_type){
        $isexist = TransferLog::where('user_id',$uid)->where('transfer_type',$transfer_type)->where('platform_type',$platform_type)->where('betid',$betid)->first();
        if($isexist){
            return true;
        }else{
            return false;
        }
    }

    public function agentFanshui($uid,$money,$fee,$platform_type,$betid,$settlementlevel,$layer)
    {
        if($settlementlevel>$layer){
			$userinfo =  \App\Models\User::where("id", $uid)->first();
			if($userinfo && $userinfo->pid) {
				$puser = \App\Models\User::where("id", $userinfo->pid)->first();
				if ($puser && !intval($puser->fanshuifee)) {
					$agent = AgentSettlement::where('id', $puser->settlement_id)->first();
					if($agent){
						$puser->fanshuifee = $agent->member_fs;
						$puser->save();						
					}else{
						$agent = AgentSettlement::where('state', 1)->first();
						if($agent){
							$puser->fanshuifee = $agent->member_fs;
							$puser->save();							
						}else{
							$puser->fanshuifee = 0;
							$puser->save();							
						}
					}
				}
				$moneys = round(($puser->fanshuifee - $fee) * $money / 100, 2);
				// var_dump($moneys);
				if ($moneys > 0) {
					$webcontent = SystemConfig::query()->find("settlement");
					$content = $webcontent['value'];
					$arr = [
						'order_no' => date('Ymd') . '_' . $puser->id . '_' . time(),
						'api_type' => 'web',
						'user_id' => $puser->id,
						'transfer_type' => 20,
						'money' => $moneys,
						'cash_fee' => 0,
						'real_money' => $moneys,
						'before_money' => $puser->balance,
						'after_money' => $puser->balance + $moneys,
						'state' => 2,
						'addtime' => time(),
						'platform_type' => $platform_type,
						'settlementsday' => $content,
						'betid' => $betid
					];
					TransferLog::create($arr);
				}
				$layer++;
				self::agentFanshui($puser->id, $money, $puser->fanshuifee, $platform_type, $betid, $settlementlevel, $layer);
			}
        }

    }
    /**
     * 解密函数
     *
     * @param Request $request
     * @return void
     */
    public function encryptData($data)
    {
        $sgin = $data['sgin'];
        unset($data['sgin']);
        ksort($data);
        $str = '';
        $i = 0;
        $sysinfo = SystemConfig::where("key","zgp_secret")->first();
        if (!$sysinfo) return 202;
        foreach($data as $key=>$v){
            if($i==0){
                $str .= $key.'='.$v;
            }else{
                $str .= '&'.$key.'='.$v;
            }
            $i++;
        }
        $str .=$sysinfo['value'];
        if($sgin != md5($str)){
            return 203;
        }
        return 0;

    }

    /**
     * 修改密码
     *
     * @param Request $request
     * @return void
     */
    public function editPassword(Request $request)
    {
        $rules = [
            'password' => 'required|min:6',
            'paypassword' => 'required|min:6',
    	];
        $this->validate($request,$rules,$this->messages);

        $data = $request->all();
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        if (!Hash::check($data['password'],$user->password)) return $this->returnMsg(205,[],'登录密码错误');
        $user->password = Hash::make($data['paypassword']);
        $user->save();
        if($user->save()){
            return $this->returnMsg(200);
        }else{
            return $this->returnMsg(300,[],'设置失败');
        }
    }

    public function editPayPasswordDo(Request $request)
    {
        $rules = [
            'password' => 'required|min:6',
            'paypassword' => 'required|min:6',
        ];
        $this->validate($request,$rules,$this->messages);
        $data = $request->all();
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();

        if (!Hash::check($data['password'], $user->paypwd)) return $this->returnMsg(205,[],'登录密码错误');
        $user->paypwd = Hash::make($data['paypassword']);
        if($user->save()){
            return $this->returnMsg(200);
        }else{
            return $this->returnMsg(300,[],'设置失败');
        }

    }
    

    public function autoagentfanshui(){

        $settlementday = intval(SystemConfig::getValue('settlement'));
        $diffday = strtotime(date('Y-m-d'))-$settlementday*60*60*24;
        $userlist = User::where('settlementday','<=',$diffday)->where('isagent','=',1)->get();
        foreach ($userlist as $val){
            $transfermoney = TransferLog::where("state",2)->where('user_id',$val->id)->where('transfer_type',20)->where('addtime','>=',$diffday)->sum('money');

            $child = User::getChild($val->id);
            $list = User::whereIn('id',$child)->get();
            $totalfanhui = 0;
            $totalredpacketSum =0;
            $totalRechargeredpacketSum =0;
            foreach ($list as $k => $v) {
                //反水
                $totalfanhui += User::totalfanhui($v->id, date('Y-m-d', $diffday) . ' 00:00:00', date('Y-m-d', time()) . ' 23:59:59');
                //紅包
                $totalredpacketSum +=   User::redpacketSum($v->id, date('Y-m-d', $diffday) . ' 00:00:00', date('Y-m-d', time()) . ' 23:59:59');
                // 充值送红包
                $totalRechargeredpacketSum +=   User::RechargeredpacketSum($v->id, date('Y-m-d', $diffday) . ' 00:00:00', date('Y-m-d', time()) . ' 23:59:59');
            }
             $user = User::where('id',$val->id)->first();
            $money =  $transfermoney -  $totalfanhui - $totalredpacketSum - $totalRechargeredpacketSum;
            if ($money>0) {

                $user->balance = $user->balance + $money;

                TransferLog::where("state",2)->where('user_id',$val->id)->where('transfer_type',20)->where('addtime','>=',$diffday)->update(['state'=>1]);
            }
                $user->settlementday = strtotime(date('Y-m-d'));
                $user->save();
        }
    }
    
    /**
     * 用户信息
     *
     * @return void
     */
    public function user(Request $request)
    {
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        $money = TransferLog::where('user_id', $user->id)
            ->where('state', 0)->sum('money');
        $info = SystemConfig::where('key','usdt_rate')->first();
        $info_withdraw = SystemConfig::where('key','withdraw_usdt_rate')->first();
        $user->fanshui = $money;
        $data['id'] = $user->id;  // 添加用户ID
        $data['api_token'] = $user->api_token;
        $data['balance'] = $user->balance;
        $data['birthday'] = $user->birthday;
        $data['fanshui'] = $user->fanshui;
        $data['realname'] = $user->realname;
        $data['transferstatus'] = $user->transferstatus;
        $data['username'] = $user->username;
        $data['level'] = $user->level;
        $data['vip'] = $user->vip;
        $data['joinday'] = intval((time()-strtotime($user->created_at))/60/60/24);
        $data['gameblance'] =Usersmoney::getTotalAppUserBalance($user->id);
        $data['avatar'] = ($user->avatar) ? env('APP_URL').$user->avatar : '';
        $data['mobile'] = $user->phone;
        $data['email'] = $user->mail;
        $data['birthday'] = $user->birthday;
        $data['usdtrate'] = $info->value;
        $data['withdrawusdtrate'] =$info_withdraw->value;   
        $info_withdrawcashfee = SystemConfig::where('key','withdraw_fee_usdt_erc')->first();
        $data['withdrawcashfee'] =$info_withdrawcashfee->value;  
        $info_withdrawfeeusdttrc = SystemConfig::where('key','withdraw_cash_fee')->first();
        $data['withdrawfeeusdttrc'] =$info_withdrawfeeusdttrc->value; 
        $data['paysum'] = $user->paysum;
        $data['totalgame'] = $user->totalgame;
        $data['isagent'] = $user->isagent;
        $uservip = UserVip::where('id',$user->vip)->first();
        $data['current_vip'] = $uservip->vipname;
        $next = UserVip::where('id','>',$user->vip)->select('vipname')->first();
        $data['next_vip'] = $next['vipname'] ?? '';
        if($uservip){
                $data['vipname'] =  '/static/style/'.strtolower($uservip->vipname).'.png';
            }else{
                $data['vipname'] =  '/static/style/'.strtolower('VIP0').'.png';
         }
        // 添加注册日期
        $data['created_at'] = $user->created_at;
        return $this->returnMsg(200,$data);
    }
    /**
     * 用户信息
     *
     * @return void
     */
    public function uploadimg(Request $request)
    {
		return $this->returnMsg(201,'','上传失败');
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        $data = $request->all();
        \Illuminate\Support\Facades\Log::info("上传回调结果");
        \Illuminate\Support\Facades\Log::info($_FILES);
        \Illuminate\Support\Facades\Log::info(json_encode($_FILES));    
        
        $filename=$_FILES['file']['name'];
        $type=$_FILES['file']['type'];
        // echo $type;
        $fileTypes = array('image/png','image/jpg','image/jpeg');
        if (!in_array($type,$fileTypes)){
            return $this->returnMsg(201,'','上传失败');
        }
        $tmp_name=$_FILES['file']['tmp_name'];
        $size=$_FILES['file']['size'];
        $error=$_FILES['file']['error'];
        $temp = explode('.',$filename);
        $name = $temp[0];
        $typePic = $temp[1];
        $filename = time().".".$typePic;
        $save = '/uploads/avatar/'.basename($filename);
        $stored_path = APPPATH.$save;
        $res = move_uploaded_file($tmp_name, $stored_path);
        $httpsStr = env('APP_URL');
        $stored_path = $httpsStr.$save;
        $user->avatar = $save;
        $rest = $user->save();
        //$rest = $this->uploadImgSql($types,$stored_path,$user_id,$rid);
        if ($rest){
           echo $stored_path;
        }else{
           echo ''; 
        }         
    }    
    /**
     * 用户信息
     *
     * @return void
     */
    public function updateuserinfo(Request $request)
    {
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        $data = $request->all();

        $user->birthday = $data['birthday'];
        $user->phone = $data['mobile'];
        $user->mail = $data['email'];
        if (isset($data['realname'])) {
            $user->realname = $data['realname'];
        }
        $rest = $user->save();
        //$rest = $this->uploadImgSql($types,$stored_path,$user_id,$rid);
        if ($rest){
            return $this->returnMsg(200);
        }else{
            return $this->returnMsg(500);
        }
        
        
       
    }    
    
    public function getUserBalance(Request $request)
    {
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $balance = User::where('api_token',$token)->value('balance');
        return $this->returnMsg(200,['balance' => $balance]);
    }
    
    public function logoff(Request $request)
    {
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $balance = User::where('api_token',$token)->update(['api_token' => Str::random(60)]);
        return $this->returnMsg(200);
    }
        
}
