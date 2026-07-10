<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    
    public function getFullPicAttribute()
    {
        return env('APP_URL').'/uploads/'.$this->pic;
    }
}
