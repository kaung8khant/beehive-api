<?php

namespace App\Repositories;

use App\Helpers\FileHelper;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;
    protected $table;

    public function __construct(Model $model, $table = null)
    {
        $this->model = $model;
        $this->table = $table;
    }

    public function find($slug)
    {
        return $this->model->where('slug', $slug)->firstOrFail();
    }

    public function create(array $attributes)
    {
        $model = $this->model->create($attributes);
        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function update($slug, array $data)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();
        $model->update($data);
        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function delete($slug)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();

        foreach ($model->images as $image) {
            FileHelper::deleteFile($image->slug);
        }

        $model->delete();
    }

    private function updateImageIfExist($slug)
    {
        if (request('image_slug')) {
            FileHelper::updateFile(request('image_slug'), $this->table, $slug);
        }
    }
}
