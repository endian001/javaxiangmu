<?php

namespace App\Admin\Repositories;

use App\Models\Bank as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Bank extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
