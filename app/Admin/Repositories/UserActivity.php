<?php

namespace App\Admin\Repositories;

use Dcat\Admin\Repositories\Repository;
use App\Models\UserActivity as UserActivityModel;

class UserActivity extends Repository
{
    protected $eloquentClass = UserActivityModel::class;
}