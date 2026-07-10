<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityApply extends Model
{
    protected $table = 'activity_apply';

    protected $guarded = [];

    public function activity_data()
    {
        return $this->belongsTo('App\Models\Activity','activity_id','id');
    }

    public function user_data()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
