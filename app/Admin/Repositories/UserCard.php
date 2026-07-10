<?php

namespace App\Admin\Repositories;

use App\Models\UserCard as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class UserCard extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
