<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MediaFile extends Model
{
    use SoftDeletes;

    protected $table = 'media_files';

    protected $fillable = [
        'name',
        'mime_type',
        'type',
        'size',
        'url',
        'options',
        'folder_id',
        'user_id',
        'alt',
        'visibility',
    ];

    protected $casts = [
        'options' => 'json',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id')->withDefault();
    }

    public static function createName(string $name, int|string|null $folder): string
    {
        $index = 1;
        $baseName = $name;

        while (self::query()->where('name', $name)->where('folder_id', $folder)->withTrashed()->exists()) {
            $name = $baseName . '-' . $index++;
        }

        return $name;
    }

    public static function createSlug(string $name, string $extension, ?string $folderPath): string
    {
        $extension = ltrim($extension, '.');
        $baseName = pathinfo($name, PATHINFO_FILENAME);
        $slug = Str::slug($baseName, '-', app()->getLocale());
        $slug = $slug !== '' ? $slug : 'file';

        $folderPath = trim((string) $folderPath, '/');
        $index = 1;
        $baseSlug = $slug;

        do {
            $fileName = $slug . ($extension !== '' ? '.' . $extension : '');
            $url = $folderPath !== '' ? $folderPath . '/' . $fileName : $fileName;

            $exists = self::query()
                ->where('url', $url)
                ->withTrashed()
                ->exists();

            if ($exists) {
                $slug = $baseSlug . '-' . $index++;
            }
        } while ($exists);

        return $url;
    }
}
