<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
	protected $table = "activities";
	
	protected $guarded = [];


    
    public function type_data()
    {
        return $this->belongsTo('App\Models\ActivityType','type','id');
    }
}
