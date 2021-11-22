<?php

namespace App\Repositories;

interface EloquentRepositoryInterface
{
    public function find($slug);

    public function create(array $attributes);

    public function update($slug, array $data);

    public function delete($slug);
}
