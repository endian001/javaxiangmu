<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recharge extends Model
{
    protected $table = "recharge";

    protected $guarded = [];

    public function user_data() 
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
