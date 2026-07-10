<?php

namespace App\Admin\Repositories;

use App\Models\AgentApply as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AgentApply extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
