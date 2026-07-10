<?php

namespace App\Admin\Repositories;

use App\Models\PayType as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class PayType extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
