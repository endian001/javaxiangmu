<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCard extends Model
{
    protected $table = "user_cards";

    protected $guarded = [];
    public function user_data()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
