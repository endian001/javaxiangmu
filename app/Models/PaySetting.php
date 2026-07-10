<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaySetting extends Model
{
    protected $table = "pay_setting";

    protected $guarded = [];

    public function bank_data()
    {
        return $this->belongsTo('App\Models\Bank','bank_id','id');
    }
}
