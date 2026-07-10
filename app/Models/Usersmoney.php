<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Services\TgService;
class Usersmoney extends Model
{

    protected $table = "usersmoney";
    protected $guarded = [];

    public static function upinfo($userid,$plat_name,$balance)
    {
        $usersmoney = self::where('user_id',$userid)->first();
        $vote = strtolower($plat_name) . '_money';
        if($usersmoney) {
            $usersmoney->$vote = $balance;
            $usersmoney->save();
        }else{
            $arr['user_id'] = $userid;
            $arr[$vote] = $balance;
            self::create($arr);
        }
    }

    public static function addinfo($userid,$plat_name,$balance)
    {
        $usersmoney = self::where('user_id',$userid)->first();
        $vote = strtolower($plat_name) . '_money';
        if($usersmoney) {
            $usersmoney->$vote += $balance;
            $usersmoney->save();
        }else{
            $arr['user_id'] = $userid;
            $arr[$vote] = $balance;
            self::create($arr);
        }
    }

    public static function setmoneyinit($userid,$plat_name)
    {
        $usersmoney = self::where('user_id',$userid)->first();
        $vote = strtolower($plat_name) . '_money';
        $usersmoney->$vote = 0;
        $usersmoney->save();
    }

    public static function kouinfo($userid,$plat_name,$balance)
    {
        $usersmoney = self::where('user_id',$userid)->first();
        $vote = strtolower($plat_name) . '_money';
        if($usersmoney) {
            if($usersmoney->$vote>=$balance) {
                $usersmoney->$vote -= $balance;
                $usersmoney->save();
            }
        }
    }

    public static function getUserBalance($userid)
    {
        
        $tg = New TgService;
        $gamemoneylist = $tg->gamesalllist();        
        $usersmoney = self::where('user_id',$userid)->first();
        $i=0;
        $retdata=[];
        foreach ($gamemoneylist as $val) {
            $vote = strtolower($val['platform_code']) . '_money';
            $retdata[$i]['name']= $val['platformname'];
            $retdata[$i]['platname']= strtolower($val['platform_code']);
            $retdata[$i]['balance']=  isset($usersmoney->$vote) ? round($usersmoney->$vote,2) :0;
            $i++;
        }
        return $retdata;
    }
    public static function getTotalAppUserBalance($userid)
    {
        $tg = New TgService;
        $gamemoneylist = $tg->gamesalllist();   
        $usersmoney = self::where('user_id',$userid)->first();
        $balance =0;
        foreach ($gamemoneylist as $val) {
            $vote = strtolower($val['platform_code']) . '_money';
            $balance +=  isset($usersmoney->$vote) ? round($usersmoney->$vote,2) :0;
        }
        return $balance;
    }
    public static function getAppUserBalance($userid,$platgamename)
    {

        $usersmoney = self::where('user_id',$userid)->first();
        $i=0;
        $retdata=[];
        foreach ($platgamename as $val) {
            $vote = strtolower($val['platform_code']) . '_money';
            $retdata[$i]['name']= $val['platformname'];
            $retdata[$i]['platname']= strtolower($val['platform_code']);
            $retdata[$i]['balance']=  isset($usersmoney->$vote) ? round($usersmoney->$vote,2) :0;
            $i++;
        }
        return $retdata;
    }
}
