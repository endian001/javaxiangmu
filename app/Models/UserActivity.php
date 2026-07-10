<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'details',
        'ip',
        'user_agent',
        'device',
        'browser',
        'os',
        'url',
        'referer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}