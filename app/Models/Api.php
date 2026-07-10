<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\TgService;

class Api extends Model
{
    protected $table = "apis";

    protected $guarded = [];

    /**
     * 获取平台下余额
     *
     * @param [type] $plat
     * @return void
     */
    public function getApiAmount($plat)
    {
        $tg = new TgService;
        $res = $tg->merchantCredit();
        if ($res['code'] == 200) {
            $plat = strtolower($plat);
            $key = $plat.'_money';
            return $res['data'][$key] ?? 0;
        } else {
            return 0;
        }
        return $res;
    }
}
