<?php

namespace App\Admin\Repositories;

use App\Models\CodePay as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class CodePay extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
