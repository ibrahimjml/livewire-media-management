<?php

namespace App\Repositories\Eloquent;

use App\Models\MediaFolder;
use App\Repositories\Interfaces\MediaFolderInterface;
use Illuminate\Database\Query\Builder;

class MediaFolderRepository extends RepositoryAbstract implements MediaFolderInterface
{
    public function getFolderByParentId(int|string|null $folderId, array $params = [], bool $withTrash = false)
    {
        $params = array_merge([
            'condition' => [],
        ], $params);

        if (! $folderId) {
            $folderId = null;
        }

        $this->model = $this->model->where('parent_id', $folderId);

        if ($withTrash) {
            $params['with_trashed'] = true;
        }

        return $this->advancedGet($params);
    }

    public function createSlug(string $name, int|string|null $parentId): string
    {
        return MediaFolder::createSlug($name, $parentId);
    }

    public function createName(string $name, int|string|null $parentId): string
    {
        return MediaFolder::createName($name, $parentId);
    }

    public function getBreadcrumbs(int|string|null $parentId, array $breadcrumbs = [])
    {
        if (! $parentId) {
            return $breadcrumbs;
        }

        $folder = $this->getFirstByWithTrash(['id' => $parentId]);

        if (empty($folder)) {
            return $breadcrumbs;
        }

        $child = $this->getBreadcrumbs($folder->parent_id, $breadcrumbs);

        return array_merge($child, [
            [
                'id' => $folder->id,
                'name' => $folder->name,
            ],
        ]);
    }

    public function getTrashed(int|string|null $parentId, array $params = [])
    {
        $params = array_merge([
            'where' => [],
        ], $params);

        $data = $this->model
            ->select('media_folders.*')
            ->where($params['where'])
            ->oldest('media_folders.name')
            ->onlyTrashed();

        if (! $parentId) {
            $data->leftJoin('media_folders as mf_parent', 'mf_parent.id', '=', 'media_folders.parent_id')
                ->where(function ($query): void {
                    $query
                        ->orWhere('media_folders.parent_id', 0)
                        ->orWhere('mf_parent.deleted_at', null);
                })
                ->withTrashed();
        } else {
            $data->where('media_folders.parent_id', $parentId);
        }

        return $data->get();
    }

    public function deleteFolder(int|string|null $folderId, bool $force = false)
    {
        $child = $this->getFolderByParentId($folderId, [], $force);

        foreach ($child as $item) {
            $this->deleteFolder($item->id, $force);
        }

        if ($force) {
            $item = $this->getFirstByWithTrash(['id' => $folderId]);

            if (! empty($item)) {
                $item->forceDelete();
            }
        } else {
            $item = $this->getFirstBy(['id' => $folderId]);

            if (! empty($item)) {
                $this->delete($item);
            }
        }
    }

    public function getAllChildFolders(int|string|null $parentId, array $child = [])
    {
        if (! $parentId) {
            return $child;
        }

        $folders = $this->allBy(['parent_id' => $parentId]);

        foreach ($folders as $folder) {
            $child[$parentId][] = $folder;
            $child = $this->getAllChildFolders($folder->id, $child);
        }

        return $child;
    }

    public function getFullPath(int|string|null $folderId, ?string $path = ''): ?string
    {
        return MediaFolder::getFullPath($folderId, $path);
    }

    public function restoreFolder(int|string|null $folderId)
    {
        $child = $this->getFolderByParentId($folderId, [], true);

        foreach ($child as $item) {
            $this->restoreFolder($item->id);
        }

        $this->restoreBy(['id' => $folderId]);
    }

    public function emptyTrash(): bool
    {
        $this->model->onlyTrashed()->each(fn (MediaFolder $folder) => $folder->forceDelete());

        return true;
    }
}
