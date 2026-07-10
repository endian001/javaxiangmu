<?php

namespace App\Admin\Repositories;

use App\Models\RedEnvelopes as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class RedEnvelopes extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
