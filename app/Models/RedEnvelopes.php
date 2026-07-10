<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedEnvelopes extends Model
{
    protected $table = "red_envelopes";

    protected $guarded = [];

    public function user_data()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
