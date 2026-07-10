<?php

namespace App\Admin\Repositories;

use App\Models\TransferLog as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class TransferLog extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
