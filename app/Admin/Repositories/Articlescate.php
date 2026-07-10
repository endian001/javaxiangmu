<?php

namespace App\Admin\Repositories;

use App\Models\Articlescate as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Articlescate extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
