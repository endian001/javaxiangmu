<?php

namespace App\Admin\Repositories;

use App\Models\Template as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Template extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
