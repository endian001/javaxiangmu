<?php

namespace App\Admin\Repositories;

use App\Models\ActivityType as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ActivityType extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
