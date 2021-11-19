<?php

namespace App\Repositories\Brand;

interface BrandRepositoryInterface
{
    public function getAll();

    public function getOne($slug);

    public function create(array $data);

    public function update($brand, array $data);

    public function delete($slug);
}
