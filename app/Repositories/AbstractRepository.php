<?php

namespace App\Repositories;

use App\Exceptions\ServerException;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\RepositoryInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected $model;

    public function __construct(Model $model) {
        $this->model = $model;
    }

    public function getAll($page, $perPage) {
        if (isset($page) && $page > 0) {
            if (isset($perPage) && $perPage > 0) {
                return $this->model->paginate($perPage);
            }
            return $this->model->paginate(20);
        }
        return $this->model->all();
    }

    public function getOne($id): ?Model {
        return $this->model->findOrFail($id);
    }

    public function create(array $attributes): Model {
        return $this->model->create($attributes);
    }

    public function update($id, array $attributes): Model {
        $record = $this->model->findOrFail($id);
        if (!$record->update($attributes)) {
            throw new ServerException("Could not save data to the database.");
        }
        return $record;
    }

    public function delete($id) {
        $this->model->destroy($id);
    }
}
