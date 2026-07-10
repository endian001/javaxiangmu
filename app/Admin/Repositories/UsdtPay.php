<?php

namespace App\Admin\Repositories;

use App\Models\UsdtPay as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class UsdtPay extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
