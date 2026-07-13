<?php

namespace App\Console\Commands;

use App\Models\GameRecord;
use App\Services\TgService;
use Illuminate\Console\Command;
use App\User;
use Cache;
use App\Models\SystemConfig;
use App\Models\TransferLog;
use App\Models\AgentSettlement;

class AllAgentFanyong extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AllAgentFanyong';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '全部代理返水';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $res = Cache::pull('all_agent_fanyong');
        if ($res && $res == 1) {
            $users = User::where('isagent',1)->get();
            foreach ($users as $k => $v) {
                $id = $v->id;
                $money = 0;
                $settlementday = intval(SystemConfig::getValue('settlement'));
                $diffday = strtotime(date('Y-m-d'))-$settlementday*60*60*24;
                $val = User::where('isagent','=',1)->where('id','=',$id)->first();
                if ($val){
                    $settlement = AgentSettlement::where('id', $val->settlement_id)->first();
                    
                    // 计算当月新增会员数量
                    $currentMonthStart = date('Y-m-01 00:00:00');
                    $currentMonthEnd = date('Y-m-t 23:59:59');
                    $newMembersCount = User::UserSum($val->id, $currentMonthStart, $currentMonthEnd);
                    
                    // 检查是否达到当月新增会员数量要求
                    if ($settlement && $settlement->required_new_members > 0 && $newMembersCount < $settlement->required_new_members) {
                        continue; // 跳过未达到要求的代理
                    }
                    
                    $transfermoney = TransferLog::where("state",2)->where('user_id',$val->id)->where('transfer_type',20)->sum('money');

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
                    // $money =  $transfermoney -  $totalfanhui - $totalredpacketSum - $totalRechargeredpacketSum;
                    $money = $transfermoney;
                    if ($money>0) {

                        $user->balance = $user->balance + $money;

                        TransferLog::where("state",2)->where('user_id',$val->id)->where('transfer_type',20)->update(['state'=>1]);
                    }
                        $user->settlementday = strtotime(date('Y-m-d'));
                        $user->save();
                }  
            }
        }
    }
}
