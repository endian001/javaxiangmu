<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Userredpacket extends Model
{

    protected $table = 'userredpacket';
    protected $guarded = [];
    public function user_data()
    {
        return $this->belongsTo('App\User','uid','id');
    }
}
