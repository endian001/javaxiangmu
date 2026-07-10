<?php

namespace App\Admin\Repositories;

use App\Models\UserOperateLog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class UserOperateLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
