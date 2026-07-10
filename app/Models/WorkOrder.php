<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes;

    protected $table = 'work_orders';

    protected $guarded = [];

    public function replies()
    {
        return $this->hasMany(WorkOrderReply::class, 'work_order_id')->orderBy('id', 'asc');
    }
}
