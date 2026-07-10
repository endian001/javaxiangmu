<?php

namespace App\Admin\Repositories;

use App\Models\WorkOrder as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class WorkOrder extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
