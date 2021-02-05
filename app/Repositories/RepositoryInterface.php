<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    public function getAll($page, $perPage);

    public function getOne($id): ?Model;

    public function create(array $attributes): Model;

    public function update($id, array $attributes): Model;

    public function delete($id);
}
