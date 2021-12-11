<?php

namespace App\Repositories;

use App\Events\DataChanged;
use App\Helpers\FileHelper;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;
    protected $user;

    public function __construct(Model $model)
    {
        $this->model = $model;

        if (auth('users')->check()) {
            $this->user = auth('users')->user();
        }
    }

    public function find($slug)
    {
        return $this->model->where('slug', $slug)->firstOrFail();
    }

    public function create(array $attributes)
    {
        $model = $this->model->create($attributes);

        if (auth('users')->check()) {
            DataChanged::dispatch($this->user, 'create', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        }

        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function update($slug, array $attributes)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();
        $model->update($attributes);

        if (auth('users')->check()) {
            DataChanged::dispatch($this->user, 'update', $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        }

        $this->updateImageIfExist($model->slug);
        return $model;
    }

    public function delete($slug)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();

        if ($model->images) {
            foreach ($model->images as $image) {
                FileHelper::deleteFile($image->slug);
            }
        }

        if (auth('users')->check()) {
            DataChanged::dispatch($this->user, 'delete', $this->model->getTable(), $model->slug, request()->url(), 'success', $model);
        }

        $model->delete();
        return response()->json(['message' => 'Successfully deleted.'], 200);
    }

    public function toggleEnable($slug)
    {
        $model = $this->model->where('slug', $slug)->firstOrFail();
        $attributes = ['is_enable' => !$model->is_enable];
        $model->update($attributes);

        if (auth('users')->check()) {
            $status = $model->is_enable ? 'enable' : 'disable';
            DataChanged::dispatch($this->user, $status, $this->model->getTable(), $model->slug, request()->url(), 'success', $attributes);
        }

        return response()->json(['message' => 'Success.'], 200);
    }

    protected function updateImageIfExist($slug)
    {
        if (request('image_slug')) {
            FileHelper::updateFile(request('image_slug'), $this->model->getTable(), $slug);
        }
    }

    protected function updateCoversIfExist($slug)
    {
        if (request('cover_slugs')) {
            foreach (request('cover_slugs') as $coverSlug) {
                FileHelper::updateFile($coverSlug, $this->model->getTable(), $slug);
            }
        }
    }
}
