<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = "system_config";

    protected $guarded = [];

    public $timestamps = false;

    /**
     * 获取配置值
     *
     * @param [type] $key
     * @return void
     */
    public static function getValue($key)
    {
        // if (in_array($key,['recharge_apply_audio','withdraw_apply_audio','activity_apply_audio','agent_apply_audio'])) {
        //     $res = self::where('key',$key)->value('value') ?? '';
        //     return $res ? '/uploads/'.$res : '';
        // }
        return self::where('key',$key)->value('value') ?? '';
    }
}
