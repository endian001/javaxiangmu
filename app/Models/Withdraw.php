<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    protected $table = 'withdraws';

    protected $guarded = [];

    public function card_data()
    {
        return $this->belongsTo('App\Models\UserCard','card_id','id');
    }

    public function user_data()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
