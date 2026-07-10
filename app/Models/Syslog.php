<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Syslog extends Model
{

    protected $table = 'syslog';
    
    protected $guarded = [];
    
    public function user_data()
    {
        return $this->belongsTo('App\User','uid','id');
    }    
}
