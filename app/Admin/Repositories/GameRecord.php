<?php

namespace App\Admin\Repositories;

use App\Models\GameRecord as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class GameRecord extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
