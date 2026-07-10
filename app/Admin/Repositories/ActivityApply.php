<?php

namespace App\Admin\Repositories;

use App\Models\ActivityApply as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ActivityApply extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
