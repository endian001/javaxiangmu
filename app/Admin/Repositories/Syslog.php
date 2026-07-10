<?php

namespace App\Admin\Repositories;

use App\Models\Syslog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Syslog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
