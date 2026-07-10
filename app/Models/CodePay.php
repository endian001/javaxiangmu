<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodePay extends Model
{
    protected $table = "code_pay";

    protected $guarded = [];

    /**
     * 关联支付类型
     */
    public function payType()
    {
        return $this->belongsTo(PayType::class, 'pay_type_id');
    }
}
