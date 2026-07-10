<?php
//decode by http://www.yunlu99.com/
namespace App;
use App\Models\GameRecord;
use App\Models\Recharge;
use App\Models\SystemConfig;
use App\Models\TransferLog;
use App\Models\Usersmoney;
use App\Models\Withdraw;
use App\Models\Users;
use App\Models\UserVip;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\SafeGameTransferService;
use App\Services\TgService;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getCustomAttribute()
    {
          if(Auth::user()) {
            $uservip = UserVip::where('id',Auth::user()->vip)->first();
            if($uservip){
                return $uservip->vipname;
            }else{
                return 'VIP0';
            }
          }        
       
    }
    /**
     * 进入游戏后自动转账到游戏账户
     * @return void
     */
    public function transToTgAccount($plat_name, $game_type)
    {
        $plat_name = ($plat_name == 'fgdz') ? 'fg' : $plat_name;
        $modelUser = $this->walletModelUser();
        if (!$modelUser) {
            return false;
        }

        $transferService = new SafeGameTransferService();
        $result = $transferService->autoMoveToPlatform($modelUser, $plat_name, new TgService);

        return ($result['code'] ?? 201) == 200;
    }

    protected function walletModelUser()
    {
        return \App\Models\User::where('id', $this->id)->first();
    }

    protected function walletTransferReturn(array $result)
    {
        if (($result['code'] ?? 201) == 200) {
            return ['code' => 200];
        }

        return ['code' => 100, 'message' => $result['message'] ?? 'transfer failed'];
    }

    /**
     * 余额转账到游戏
     * @return void
     */
    public function transToAccount($plat_name, $money)  // 余额转账到游戏
    {
        $tg = new TgService;
        $modelUser = $this->walletModelUser();
        if (!$modelUser) {
            return ['code' => 100, 'message' => 'login expired'];
        }

        $transferService = new SafeGameTransferService();
        $result = $transferService->moveAccountBalanceToPlatform($modelUser, $plat_name, $money, $tg, 'web_manual_transfer');

        return $this->walletTransferReturn($result);

        if ($this->balance >= $money) {
            $client_transfer_id = time() . $this->id . rand(1000, 9999);

            $amount = -$money;
            $arr = [
                'order_no' => $client_transfer_id,
                'api_type' => $plat_name,
                'user_id' => $this->id,
                'transfer_type' => 0,
                'money' => -$money,
                'cash_fee' => 0,
                'real_money' => $amount,
                'before_money' => $this->balance,
                'after_money' => $this->balance + $amount,
                'state' => 2
            ];
            TransferLog::create($arr);

            $res = ['code' => 201, 'message' => 'legacy transfer disabled'];

            if ($res['code'] == 200) {
                $this->balance = $this->balance - $money;
                $this->save();
                $transferlog = TransferLog::where('order_no', $client_transfer_id)->first();
                $transferlog->state = 1;
                $transferlog->save();
                Usersmoney::addinfo($this->id, $plat_name, $money);
                return array('code' => 200);
            } else {
                return array('code' => 100, 'message' => $res['message']);
            }

        } else {
            return array('code' => 100, 'message' => '账户余额不足');
        }
    }

    /**
     * 代理佣金总笔数
     *
     * @return void
     */
    public static function gamecount($userid)
    {
        // dd($userid);
        $count = TransferLog::where('user_id', $userid)->where('state', 2)->where('transfer_type', 20)->count();

        return $count;
    }
    /**
     * 代理佣金金额
     *
     * @return void
     */
    public static function gamemoney($userid)
    {
        // dd($userid);
        $count = TransferLog::where('user_id', $userid)->where('state', 2)->where('transfer_type', 20)->sum('money');

        return $count ? $count : 0;
    }
    /**
     * 代理佣金投注金额
     *
     * @return void
     */
    public static function amountsum($userid,$name)
    {
        // dd($userid);
        $count = TransferLog::where('user_id', $userid)->where('state', 2)->where('transfer_type', 20)->get()->toArray();
		$betid = [];
        foreach($count as $key => $value){
			$betid[] = $value['betid'];
		}
		$amountsum = GameRecord::whereIn('bet_id', $betid)->where('status', 1)->sum($name);

        return $amountsum ? $amountsum : 0;
    }	
    /**
     * 游戏转账到余额
     * @return void
     */
    public function Accounttranso($plat_name, $money)   // 游戏转账到余额
    {
        $tg = new TgService;
        $modelUser = $this->walletModelUser();
        if (!$modelUser) {
            return ['code' => 100, 'message' => 'login expired'];
        }

        $transferService = new SafeGameTransferService();
        $result = $transferService->movePlatformBalanceToAccount($modelUser, $plat_name, $money, $tg, 'web_manual_transfer');

        return $this->walletTransferReturn($result);


        $client_transfer_id = time() . $this->id . rand(1000, 9999);

        $amount = abs($money);
        $arr = [
            'order_no' => $client_transfer_id,
            'api_type' => $plat_name,
            'user_id' => $this->id,
            'transfer_type' => 1,
            'money' => $money,
            'cash_fee' => 0,
            'real_money' => $amount,
            'before_money' => $this->balance ,
            'after_money' => $this->balance + $amount,
            'state' => 2
        ];
        TransferLog::create($arr);

        $res = ['code' => 201, 'message' => 'legacy transfer disabled'];

        if ($res['code'] == 200) {
            $this->balance = $this->balance + $money;
            $this->save();
            $transferlog = TransferLog::where('order_no', $client_transfer_id)->first();
            $transferlog->state = 1;
            $transferlog->save();
            Usersmoney::kouinfo($this->id, $plat_name, $money);
            return array('code' => 200);
        } else {
            return array('code' => 100, 'message' => $res['message']);
        }
    }




    /**
     * 一键回收
     * @return void
     */
    public function AllAccounttranso($plat_name, $money)   // 游戏转账到余额
    {
        $client_transfer_id = time() . $this->id . rand(1000, 9999);
        $amount = abs($money);
        $arr = [
            'order_no' => $client_transfer_id,
            'api_type' => $plat_name,
            'user_id' => $this->id,
            'transfer_type' => 1,
            'money' => $money,
            'cash_fee' => 0,
            'real_money' => $amount,
            'before_money' => $this->balance ,
            'after_money' => $this->balance + $amount,
            'state' => 1
        ];
        TransferLog::create($arr);
        $this->balance = $this->balance + $money;
        $this->save();
        Usersmoney::kouinfo($this->id, $plat_name, $money);
        return array('code' => 200);
       
    }

    /**
     * 代理下级集合
     *
     * @param [type] $user_id
     * @return void
     */
    public static function getChild($user_id, $layer = 0)
    {
        $settlementlevel = (intval(SystemConfig::getValue('settlementlevel')) == 0) ? 20 : intval(SystemConfig::getValue('settlementlevel'));
        $arr = [];
        if ($settlementlevel >= $layer) {
            $user = self::where('pid', $user_id)->pluck('id');
            if (!$user) {
                return $arr;
            } else {
                $layer++;
                foreach ($user as $k => $v) {
                    $arr[] = $v;
                    $arr = array_merge($arr, self::getChild($v, $layer));
                }
            }

            return $arr;
        } else {
            return $arr;
        }
    }


    /**
     * 代理下级集合
     *
     * @param [type] $user_id
     * @return void
     */
    public static function getChildNext($user_id)
    {
        $arr = [];
        $user = self::where('pid', $user_id)->pluck('id');
        if (!$user) {
            return $arr;
        } else {
            foreach ($user as $k => $v) {
                $arr[] = $v;
                $arr = array_merge($arr, self::getChild($v));
            }
        }
        return $arr;
    }

    public static function getChildMember($user_id)
    {
        $arr = [];
        $user = self::where('pid', $user_id)->where('isagent', 0)->pluck('id');
        if (!$user) {
            return $arr;
        } else {
            foreach ($user as $k => $v) {
                $arr[] = $v;
                $arr = array_merge($arr, self::getChild($v));
            }
        }
        return $arr;
    }

    public static function getChildAgent($user_id)
    {
        $arr = [];
        $user = self::where('pid', $user_id)->where('isagent', 1)->pluck('id');
        if (!$user) {
            return $arr;
        } else {
            foreach ($user as $k => $v) {
                $arr[] = $v;
                $arr = array_merge($arr, self::getChild($v));
            }
        }
        return $arr;
    }


    /**
     * 注册时间
     *
     * @return void
     */
    public function registerDay()
    {
        $day = 1;
        $register_time = strtotime(Auth::user()->created_at);
        $day = ceil((time() - $register_time) / 86400);
        return $day;
    }


    /**
     * 获取用户某平台下注总和
     *
     * @param [type] $plat
     * @return void
     */
    public function getBetRecord($plat)
    {
        $plat = strtolower($plat);
        $sum = GameRecord::where('user_id', $this->id)
            ->where('platform_type', $plat)
            ->where('status', '>', 0)
            ->sum('bet_amount');
        return $sum;
    }


    /**
     * 代理信息
     *
     * @return void
     */
    public function getAgentData()
    {
        if ($this->pid) {
            $agent = $this->find($this->pid);
            return $agent;
        } else {
            return [];
        }
    }

    /**
     * 下级会员人数
     *
     * @return void
     */
    public static function UserSum($userid, $start = '', $end = '')
    {
        // dd($userid);
        $count = Users::where('pid', $userid)->where('status', 1)->where('isagent', 0)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->count();

        return $count;
    }

    /**
     * 下级代理人数
     *
     * @return void
     */
    public static function AgentSum($user_id, $start = '', $end = '')
    {
        $count = Users::where('pid', $user_id)->where('status', 1)->where('isagent', 1)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->count();
        return $count;
    }

    /**
     * 已结算佣金
     *
     * @return void
     */
    public static function Agentyongjin($user_id, $start = '', $end = '')
    {
        $count = TransferLog::where('user_id', $user_id)->where('transfer_type', 20)->where('state', 1)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum("money");
        return $count;
    }
    
    public static function Agentyongjin2($user_id, $start = '', $end = '')
    {
        $child = self::getChild($user_id);
        array_push($child,$user_id);
        $count = TransferLog::whereIn('user_id', $child)->where('transfer_type', 20)->where('state', 1)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum("money");
        return $count;
    }

    /**
     * 未结算佣金
     *
     * @return void
     */
    public static function Agentyongjinwait($user_id, $start = '', $end = '')
    {
        $count = TransferLog::where('user_id', $user_id)->where('transfer_type', 20)->where('state', 2)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum("money");
        return $count;
    }

    /**
     * 红包
     *
     * @return void
     */
    public static function Redpacket($user_id, $start = '', $end = '')
    {
        $count = TransferLog::where('user_id', $user_id)->where('transfer_type', 5)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum("money");
        return $count;
    }


    /**
     * 充值次数
     *
     * @return void
     */
    public static function rechargeTimes($user_id, $start = '', $end = '')
    {
        $count = Recharge::where('user_id', $user_id)->where('state', 2)->whereIn('pay_way', [1, 2, 3, 4,5,6,7])
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->count();
        return $count;
    }

    /**
     * 提现次数
     *
     * @return void
     */
    public static function withdrawTimes($user_id, $start = '', $end = '')
    {
        $count = Withdraw::where('user_id', $user_id)->where('state', 2)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->count();
        return $count;
    }

    /**
     * 总充值
     *
     * @return void
     */
    public static function rechargeSum($user_id, $start = '', $end = '')
    {
        $sum = Recharge::where('user_id', $user_id)->where('state', 2)->whereIn('pay_way', [1, 2, 3, 4,5,6,7])
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('amount');
        return $sum;
    }

    /**
     * 总红包
     *
     * @return void
     */
    public static function redpacketSum($user_id, $start = '', $end = '')
    {
        $sum = Recharge::where('user_id', $user_id)->where('state', 2)->where('pay_way', 10)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('amount');
        return $sum;
    }

    /**
     * 总提现
     *
     * @return void
     */
    public static function withdrawSum($user_id, $start = '', $end = '')
    {
        $sum = Withdraw::where('user_id', $user_id)->where('state', 2)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('amount');
        return $sum;
    }

    /**
     * 下注次数
     *
     * @return void
     */
    public static function betTimes($user_id)
    {
        $count = GameRecord::where('user_id', $user_id)->count();
        return $count;
    }

    /**
     * 下注统计
     *
     * @return void
     */
    public static function betSum($user_id)
    {
        $sum = GameRecord::where('user_id', $user_id)->where('status', '>', 0)->sum('bet_amount');
        return $sum;
    }

    /**
     * 有效下注统计
     */
    public static function vaildBetSum($user_id, $start = '', $end = '')
    {
        $sum = GameRecord::where('user_id', $user_id)->where('status', '>', 0)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('valid_amount');
        return $sum;
    }


    /**
     * 有效下注统计
     */
    public static function vaildBetCount($user_id, $start = '', $end = '')
    {
        $sum = GameRecord::where('user_id', $user_id)->where('status', '>', 0)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->count();
        return $sum;
    }

    /**
     * 输赢总金额
     */
    public static function totalfanhui($user_id, $start = '', $end = '')
    {
        $sum = TransferLog::where('user_id', $user_id)->where('transfer_type', 6)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('money');
        return $sum;
    }

    /**
     * 输赢总金额
     */
    public static function winLoss($user_id, $start = '', $end = '')
    {
        $sum = GameRecord::where('user_id', $user_id)->where('status', '>', 0)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('win_loss');
        return $sum;
    }

    /**
     * 获取用户某平台下注总和
     *
     * @param [type] $plat
     * @return void
     */
    public function getBetSumByPlat($plat)
    {
        $plat = strtolower($plat);
        $sum = GameRecord::where('user_id', $this->id)
            ->where('platform_type', $plat)
            ->where('status', '>', 0)
            ->sum('bet_amount');
        return $sum;
    }

    /* 获取每天数据 */
    public static function getBetDayDta($user_id, $day)
    {

        $child = self::getChild($user_id);
        $list = Users::whereIn('id', $child)->get();
        $all_recharge = 0;
        $all_withdraw = 0;
        $all_valid_bet = 0;
        $all_win_loss = 0;
        $allrecharge = 0;
        $allwithdraw = 0;
        $ret = array();
        for ($i = 0; $i < $day; $i++) {
            $start = date("Y-m-d", strtotime("-" . ($day - $i) . " day"));
            $end = date("Y-m-d", strtotime("-" . ($day - $i + 1) . " day"));
            $showstart = date("m-d", strtotime("-" . ($day - $i) . " day"));
            foreach ($list as $k => $v) {
                $all_recharge += self::rechargeSum($v->id, $start, $end); //总存款
                $all_withdraw += self::withdrawSum($v->id, $start, $end); //总提款
                $all_valid_bet += self::vaildBetSum($v->id, $start, $end); //总有效投注
                $all_win_loss += self::totalfanhui($v->id, $start, $end); //总输赢
            }
            /*            $ret['day'][] = $start;
                        $ret['all_recharge'][] = $all_recharge;
                        $ret['all_withdraw'][] = $all_withdraw;
                        $ret['all_valid_bet'][] = $all_valid_bet;
                        $ret['all_win_loss'][] = $all_win_loss;*/
            if ($i == 0) {
                $ret['day'] = $showstart;
                $ret['all_recharge'] = $all_recharge;
                $ret['all_withdraw'] = $all_withdraw;
                $ret['all_valid_bet'] = $all_valid_bet;
                $ret['all_win_loss'] = $all_win_loss;
            } else {
                $ret['day'] .= ',' . $showstart;
                $ret['all_recharge'] .= ',' . $all_recharge;
                $ret['all_withdraw'] .= ',' . $all_withdraw;
                $ret['all_valid_bet'] .= ',' . $all_valid_bet;
                $ret['all_win_loss'] .= ',' . $all_win_loss;
            }
        }
        foreach ($list as $k => $v) {
            $allrecharge += self::rechargeSum($v->id, '', ''); //总存款
            $allwithdraw += self::withdrawSum($v->id, '', ''); //总存款
        }
        $ret['allrecharge'] = $allrecharge;
        $ret['allwithdraw'] = $allwithdraw;
        /*$ret['day'] =  '[' . $ret['day'] . ']';
        $ret['all_recharge'] =  '[' . $ret['all_recharge'] . ']';
        $ret['all_withdraw'] =  '[' . $ret['all_withdraw'] . ']';
        $ret['all_valid_bet'] =  '[' . $ret['all_valid_bet'] . ']';
        $ret['all_win_loss'] =  '[' . $ret['all_win_loss'] . ']';*/
        return $ret;

    }


    /**
     * 注册时间
     *
     * @return void
     */
    public function agentregisterDay()
    {
        $day = 1;
        $register_time = strtotime(Auth::user()->created_at);
        $day = ceil((time() - $register_time) / 86400);
        return $day;
    }

    /**
     * 代理信息
     *
     * @return void
     */
    public function agentgetAgentData()
    {
        if ($this->pid) {
            $agent = $this->where('id', $this->pid)->first();
            return $agent;
        } else {
            return [];
        }
    }

    /**
     * 充值次数
     *
     * @return void
     */
    public function agentrechargeTimes()
    {
        $count = Recharge::where('user_id', $this->id)->where('state', 2)->count();
        return $count;
    }

    /**
     * 提现次数
     *
     * @return void
     */
    public function agentwithdrawTimes()
    {
        $count = Withdraw::where('user_id', $this->id)->where('state', 2)->count();
        return $count;
    }

    /**
     * 总充值
     *
     * @return void
     */
    public function agentrechargeSum()
    {
        $sum = Recharge::where('user_id', $this->id)->where('state', 2)->sum('amount');
        return $sum;
    }

    /**
     * 总提现
     *
     * @return void
     */
    public function agentwithdrawSum()
    {
        $sum = Withdraw::where('user_id', $this->id)->where('state', 2)->sum('amount');
        return $sum;
    }

    /**
     * 下注次数
     *
     * @return void
     */
    public function agentbetTimes()
    {
        $count = GameRecord::where('user_id', $this->id)->count();
        return $count;
    }

    /**
     * 下注统计
     *
     * @return void
     */
    public function agentbetSum()
    {
        $child = self::getChild($this->id);
        array_push($child,$this->id);
        $sum = GameRecord::whereIn('user_id', $child)->where('status', '>', 0)->sum('bet_amount');
        return $sum;
    }

    /**
     * 有效下注统计
     */
    public function agentvaildBetSum()
    {
        $child = self::getChild($this->id);
        array_push($child,$this->id);
        $sum = GameRecord::whereIn('user_id', $child)->where('status', '>', 0)->sum('valid_amount');
        return $sum;
    }

    /**
     * 输赢总金额
     */
    public function agentwinLoss()
    {
        $child = self::getChild($this->id);
        array_push($child,$this->id);
        $sum = GameRecord::whereIn('user_id', $child)->where('status', '>', 0)->sum('win_loss');
        return $sum;
    }

    /**
     * 充值送红包
     *
     * @return void
     */
    public static function RechargeredpacketSum($user_id, $start = '', $end = '')
    {
        $sum = TransferLog::where('user_id', $user_id)->where('state', 1)->where('transfer_type', 5)
            ->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->sum('money');
        return $sum;
    }
}
