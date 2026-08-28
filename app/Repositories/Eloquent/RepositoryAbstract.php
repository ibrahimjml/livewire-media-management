<?php

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class RepositoryAbstract
{
    protected Builder|Model $model;

    protected Model $originalModel;

    public function __construct(Builder|Model $model)
    {
        if ($model instanceof Builder) {
            $model = $model->getModel();
        }

        $this->model = $model;
        $this->originalModel = $model;
    }

    public function resetModel(): self
    {
        $this->model = $this->originalModel->newInstance();

        return $this;
    }

    protected function query(): Builder
    {
        return $this->model instanceof Builder
            ? $this->model
            : $this->model->newQuery();
    }

    protected function make(array $with = []): Builder
    {
        $query = $this->query();

        if (! empty($with)) {
            $query->with($with);
        }

        return $query;
    }

    public function create(array $data)
    {
        $item = $this->query()->create($data);
        $this->resetModel();

        return $item;
    }

    public function createOrUpdate($data, array $condition = [])
    {
        if (is_array($data)) {
            $item = empty($condition)
                ? $this->originalModel->newInstance()
                : $this->getFirstBy($condition);

            if (! $item instanceof Model) {
                $item = $this->originalModel->newInstance();
            }

            $item->fill($data);
        } elseif ($data instanceof Model) {
            $item = $data;
        } else {
            return false;
        }

        $saved = $item->save();
        $this->resetModel();

        return $saved ? $item : false;
    }

    public function getFirstBy(array $condition = [], array $select = ['*'], array $with = [])
    {
        return $this->make($with)
            ->select($select)
            ->where($condition)
            ->first();
    }

    public function getFirstByTrash(array $condition = [], array $select = ['*'], array $with = [])
    {
        return $this->make($with)
            ->withTrashed()
            ->select($select)
            ->where($condition)
            ->first();
    }

    public function getFirstByWithTrash(array $condition = [], array $select = ['*'], array $with = [])
    {
        return $this->getFirstByTrash($condition, $select, $with);
    }

    public function allBy(array $condition = [], array $select = ['*'], array $with = []): Collection
    {
        return $this->make($with)
            ->select($select)
            ->where($condition)
            ->get();
    }

    public function advancedGet(array $params = []): Collection|LengthAwarePaginator
    {
        $params = array_merge([
            'condition' => [],
            'select' => ['*'],
            'with' => [],
            'order_by' => [],
            'group_by' => [],
            'with_trashed' => false,
            'only_trashed' => false,
            'take' => null,
            'paginate' => null,
            'search' => null,
        ], $params);

        $query = $this->make($params['with'])
            ->select($params['select'])
            ->where($params['condition']);

        if ($params['with_trashed']) {
            $query->withTrashed();
        }

        if ($params['only_trashed']) {
            $query->onlyTrashed();
        }

        if (! empty($params['search']) && is_array($params['search'])) {
            $searchTerm = trim((string) ($params['search']['value'] ?? ''));
            $columns = $params['search']['fields'] ?? [];

            if ($searchTerm !== '' && ! empty($columns)) {
                $query->where(function (Builder $builder) use ($columns, $searchTerm): void {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $builder->where($column, 'like', '%' . $searchTerm . '%');
                        } else {
                            $builder->orWhere($column, 'like', '%' . $searchTerm . '%');
                        }
                    }
                });
            }
        }

        foreach ((array) $params['group_by'] as $groupBy) {
            $query->groupBy($groupBy);
        }

        foreach ((array) $params['order_by'] as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        if (! empty($params['paginate'])) {
            return $query->paginate((int) $params['paginate']);
        }

        if (! empty($params['take'])) {
            $query->take((int) $params['take']);
        }

        return $query->get();
    }

    public function update(array $condition, array $data): int
    {
        $updated = $this->query()->where($condition)->update($data);
        $this->resetModel();

        return $updated;
    }

    public function restoreBy(array $condition): int
    {
        $restored = $this->query()->withTrashed()->where($condition)->restore();
        $this->resetModel();

        return $restored;
    }

    public function select(array $select = ['*'], array $condition = []): Builder
    {
        return $this->query()->select($select)->where($condition);
    }

    public function delete(Model $model): ?bool
    {
        return $model->delete();
    }

    public function firstOrCreate(array $data, array $with = [])
    {
        $item = $this->query()->firstOrCreate($data, $with);
        $this->resetModel();

        return $item;
    }

    public function forceDelete(): void
    {
        $this->query()->withTrashed()->forceDelete();
        $this->resetModel();
    }
}
