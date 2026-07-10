<?php

namespace App\Admin\Repositories;

use App\Models\AgentSettlement as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AgentSettlement extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
