<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function find($slug): ?Model;

    public function create(array $attributes): Model;

    public function update($slug, array $data);

    public function delete($slug);
}
