<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawals extends Model
{
    protected $table = "withdrawals";

    protected $guarded = [];

    public function user_data() 
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
