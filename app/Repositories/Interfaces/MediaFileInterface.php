<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface MediaFileInterface 
{
    public function createName(string $name, int|string|null $folder): string;

    public function createSlug(string $name, string $extension, ?string $folderPath): string;

    public function getFilesByFolderId(int|string $folderId, array $params = [], bool $withFolders = true, array $folderParams = []);

    public function getTrashed(int|string $folderId, array $params = [], bool $withFolders = true, array $folderParams = []): Collection;

    public function emptyTrash(): bool;
}