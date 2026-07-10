<?php

namespace App\Admin\Repositories;

use App\Models\AgentFenxiang as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AgentFenxiang extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}