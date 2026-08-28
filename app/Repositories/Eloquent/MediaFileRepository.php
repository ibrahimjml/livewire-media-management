<?php

namespace App\Repositories\Eloquent;

use App\Models\MediaFile;
use App\Models\MediaFolder;
use App\Repositories\Interfaces\MediaFileInterface;
use Illuminate\Support\Collection;

class MediaFileRepository extends RepositoryAbstract implements MediaFileInterface
{
    public function createName(string $name, int|string|null $folder): string
    {
        return MediaFile::createName($name, $folder);
    }

    public function createSlug(string $name, string $extension, ?string $folderPath): string
    {
        return MediaFile::createSlug($name, $extension, $folderPath);
    }

    public function getFilesByFolderId(
        int|string $folderId,
        array $params = [],
        bool $withFolders = true,
        array $folderParams = []
    ) {
        $params = array_merge([
            'where' => [],
            'select' => ['*'],
            'order_by' => ['name' => 'asc'],
        ], $params);

        $query = $this->model->newQuery()
            ->select($params['select'])
            ->where('folder_id', $folderId)
            ->where($params['where']);

        foreach ($params['order_by'] as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $files = $query->get();

        if (! $withFolders) {
            return $files;
        }

        $folderParams = array_merge([
            'where' => [],
            'select' => ['*'],
            'order_by' => ['name' => 'asc'],
        ], $folderParams);

        $folderQuery = MediaFolder::query()
            ->select($folderParams['select'])
            ->where('parent_id', $folderId)
            ->where($folderParams['where']);

        foreach ($folderParams['order_by'] as $column => $direction) {
            $folderQuery->orderBy($column, $direction);
        }

        return [
            'folders' => $folderQuery->get(),
            'files' => $files,
        ];
    }

    public function getTrashed(
        int|string $folderId,
        array $params = [],
        bool $withFolders = true,
        array $folderParams = []
    ): Collection {
        $params = array_merge([
            'where' => [],
            'select' => ['*'],
            'order_by' => ['name' => 'asc'],
        ], $params);

        $query = $this->model->newQuery()
            ->onlyTrashed()
            ->select($params['select'])
            ->where('folder_id', $folderId)
            ->where($params['where']);

        foreach ($params['order_by'] as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $files = $query->get();

        if (! $withFolders) {
            return $files;
        }

        $folderParams = array_merge([
            'where' => [],
        ], $folderParams);

        $folders = MediaFolder::query()
            ->onlyTrashed()
            ->where('parent_id', $folderId)
            ->where($folderParams['where'])
            ->get();

        return collect([
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    protected function getFile(array $params)
    {
        return $this->advancedGet($params);
    }

    public function emptyTrash(): bool
    {
        $this->model->newQuery()->onlyTrashed()->forceDelete();

        return true;
    }
}
