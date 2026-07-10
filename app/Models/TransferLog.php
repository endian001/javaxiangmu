<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferLog extends Model
{
    protected $table = "transfer_logs";

    protected $guarded = [];

    public function user_data()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
