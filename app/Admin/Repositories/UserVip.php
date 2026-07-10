<?php

namespace App\Admin\Repositories;

use App\Models\UserVip as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class UserVip extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
