<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentApply extends Model
{
    protected $table = "agent_apply";

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
