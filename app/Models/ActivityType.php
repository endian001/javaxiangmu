<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
	protected $table = "activity_types";

	protected $guarded = [];

    protected $casts = [
        'state' => 'integer',
        'sort_order' => 'integer',
    ];
}
