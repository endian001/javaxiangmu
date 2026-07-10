<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
	protected $table = "activities";
	
	protected $guarded = [];

    protected $casts = [
        'type' => 'integer',
        'apply_count' => 'integer',
        'can_apply' => 'integer',
        'state' => 'integer',
        'app_state' => 'integer',
        'sort_order' => 'integer',
        'is_popup' => 'integer',
        'popup_delay_seconds' => 'integer',
        'requires_auth' => 'integer',
    ];


    
    public function type_data()
    {
        return $this->belongsTo('App\Models\ActivityType','type','id');
    }
}
