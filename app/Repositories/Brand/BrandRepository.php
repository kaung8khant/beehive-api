<?php

namespace App\Repositories\Brand;

use App\Helpers\FileHelper;
use App\Models\Brand;

class BrandRepository implements BrandRepositoryInterface
{
    public function getAll()
    {
        if (request('filter')) {
            return Brand::search(request('filter'))->paginate(10);
        } else {
            return Brand::orderBy('name', 'asc')->paginate(10);
        }
    }

    public function getOne($slug)
    {
        return Brand::where('slug', $slug)->firstOrFail();
    }

    public function create(array $data)
    {
        $brand = Brand::create($data);

        $this->updateFileIfExist($brand->slug);

        return $brand;
    }

    public function update($slug, array $data)
    {
        $brand = $this->getOne($slug);
        $brand->update($data);

        $this->updateFileIfExist($brand->slug);

        return $brand;
    }

    public function delete($slug)
    {
        $brand = $this->getOne($slug);

        foreach ($brand->images as $image) {
            FileHelper::deleteFile($image->slug);
        }

        $brand->delete();
    }

    private function updateFileIfExist($slug)
    {
        if (request('image_slug')) {
            FileHelper::updateFile(request('image_slug'), 'brands', $slug);
        }
    }
}
