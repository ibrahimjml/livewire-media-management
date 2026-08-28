<?php

use App\Models\MediaFile;
use App\Models\MediaFolder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public int $currentFolderId = 0;
    public string $search = '';
    public string $typeFilter = 'all';
    public string $sort = 'name_asc';
    public string $viewMode = 'grid';
    public ?int $selectedFileId = null;
    public ?int $selectedFolderId = null;
    public string $newFolderName = '';
    public $upload = null;

    public function openFolder(int $folderId): void
    {
        $this->currentFolderId = $folderId;
        $this->selectedFileId = null;
        $this->selectedFolderId = null;
    }

    public function goToFolder(int $folderId): void
    {
        $this->currentFolderId = $folderId;
        $this->selectedFileId = null;
        $this->selectedFolderId = null;
    }

    public function goRoot(): void
    {
        $this->currentFolderId = 0;
        $this->selectedFileId = null;
        $this->selectedFolderId = null;
    }

    public function selectFile(int $fileId): void
    {
        $this->selectedFileId = $fileId;
        $this->selectedFolderId = null;
    }

    public function selectFolder(int $folderId): void
    {
        $this->selectedFolderId = $folderId;
        $this->selectedFileId = null;
    }

    public function setTypeFilter(string $filter): void
    {
        $this->typeFilter = $filter;
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
    }

    public function updatedUpload(): void
    {
        $this->validate([
            'upload' => ['required', 'file', 'max:20480'],
        ]);

        $uploadedFile = $this->upload;
        $folderId = $this->currentFolderId === 0 ? 0 : $this->currentFolderId;
        $folderPath = $this->buildFolderPath($folderId);

        $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
        $baseName = pathinfo((string) $uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = $baseName !== '' ? $baseName : 'file';

        $uniqueName = MediaFile::createName($baseName, $folderId);
        $relativePath = MediaFile::createSlug($uniqueName, $extension, $folderPath);
        $uploadedFile->storeAs('', $relativePath, 'public');

        $mediaFile = MediaFile::query()->create([
            'user_id' => auth()->id() ?? 0,
            'folder_id' => $folderId,
            'name' => basename((string) $relativePath),
            'mime_type' => (string) ($uploadedFile->getMimeType() ?? 'application/octet-stream'),
            'size' => (int) ($uploadedFile->getSize() ?? 0),
            'url' => $relativePath,
            'options' => null,
        ]);

        $this->selectedFileId = (int) $mediaFile->id;
        $this->selectedFolderId = null;
        $this->reset('upload');
    }

    public function createFolder(): void
    {
        $this->validate([
            'newFolderName' => ['required', 'string', 'max:120'],
        ]);

        $parentId = $this->currentFolderId === 0 ? 0 : $this->currentFolderId;
        $name = MediaFolder::createName(trim($this->newFolderName), $parentId);

        $folder = MediaFolder::query()->create([
            'user_id' => auth()->id() ?? 0,
            'name' => $name,
            'slug' => MediaFolder::createSlug($name, $parentId),
            'parent_id' => $parentId,
        ]);

        $this->newFolderName = '';
        $this->selectedFolderId = (int) $folder->id;
        $this->selectedFileId = null;

        $this->dispatch('close-modal', name: 'create-media-folder');
    }

    public function moveFileToFolder(int $fileId, int $targetFolderId): void
    {
        $file = MediaFile::query()->find($fileId);
        $targetFolder = MediaFolder::query()->find($targetFolderId);

        if (! $file || ! $targetFolder) {
            return;
        }

        if ((int) $file->folder_id === (int) $targetFolderId) {
            return;
        }

        $targetPath = $this->buildFolderPath($targetFolderId);
        $originalName = pathinfo((string) $file->name, PATHINFO_FILENAME);
        $originalName = $originalName !== '' ? $originalName : pathinfo((string) $file->url, PATHINFO_FILENAME);
        $extension = strtolower((string) pathinfo((string) $file->url, PATHINFO_EXTENSION));
        $newRelativePath = MediaFile::createSlug($originalName, $extension, $targetPath);

        $oldPath = (string) $file->url;
        if ($oldPath !== $newRelativePath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->move($oldPath, $newRelativePath);
        }

        $file->update([
            'folder_id' => $targetFolderId,
            'name' => basename($newRelativePath),
            'url' => $newRelativePath,
        ]);

        $this->selectedFileId = (int) $file->id;
        $this->selectedFolderId = null;
    }

    public function moveFileToRoot(int $fileId): void
    {
        $file = MediaFile::query()->find($fileId);

        if (! $file || (int) $file->folder_id === 0) {
            return;
        }

        $originalName = pathinfo((string) $file->name, PATHINFO_FILENAME);
        $originalName = $originalName !== '' ? $originalName : pathinfo((string) $file->url, PATHINFO_FILENAME);
        $extension = strtolower((string) pathinfo((string) $file->url, PATHINFO_EXTENSION));
        $newRelativePath = MediaFile::createSlug($originalName, $extension, null);

        $oldPath = (string) $file->url;
        if ($oldPath !== $newRelativePath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->move($oldPath, $newRelativePath);
        }

        $file->update([
            'folder_id' => 0,
            'name' => basename($newRelativePath),
            'url' => $newRelativePath,
        ]);

        $this->selectedFileId = (int) $file->id;
        $this->selectedFolderId = null;
    }

    #[Computed]
    public function currentFolder(): ?MediaFolder
    {
        if ($this->currentFolderId === 0) {
            return null;
        }

        return MediaFolder::query()->find($this->currentFolderId);
    }

    #[Computed]
    public function breadcrumbs(): array
    {
        if (! $this->currentFolder) {
            return [];
        }

        $parents = $this->currentFolder->parents
            ->reverse()
            ->values()
            ->map(fn (MediaFolder $folder) => [
                'id' => (int) $folder->id,
                'name' => (string) $folder->name,
            ])
            ->all();

        $parents[] = [
            'id' => (int) $this->currentFolder->id,
            'name' => (string) $this->currentFolder->name,
        ];

        return $parents;
    }

    #[Computed]
    public function folders(): Collection
    {
        $query = MediaFolder::query();

        if ($this->currentFolderId === 0) {
            $query->where(function ($builder): void {
                $builder->where('parent_id', 0)->orWhereNull('parent_id');
            });
        } else {
            $query->where('parent_id', $this->currentFolderId);
        }

        if (trim($this->search) !== '') {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        return $query->orderBy('name')->get();
    }

    #[Computed]
    public function files(): Collection
    {
        $query = MediaFile::query();

        if ($this->currentFolderId === 0) {
            $query->where(function ($builder): void {
                $builder->where('folder_id', 0)->orWhereNull('folder_id');
            });
        } else {
            $query->where('folder_id', $this->currentFolderId);
        }

        if (trim($this->search) !== '') {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        if ($this->typeFilter === 'images') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($this->typeFilter === 'documents') {
            $query->where('mime_type', 'not like', 'image/%');
        }

        [$sortColumn, $sortDirection] = match ($this->sort) {
            'name_desc' => ['name', 'desc'],
            'newest' => ['created_at', 'desc'],
            'oldest' => ['created_at', 'asc'],
            default => ['name', 'asc'],
        };

        return $query->orderBy($sortColumn, $sortDirection)->get();
    }

    #[Computed]
    public function selectedFile(): ?MediaFile
    {
        if (! $this->selectedFileId) {
            return null;
        }

        return MediaFile::query()->find($this->selectedFileId);
    }

    #[Computed]
    public function selectedFolder(): ?MediaFolder
    {
        if (! $this->selectedFolderId) {
            return null;
        }

        return MediaFolder::query()->find($this->selectedFolderId);
    }

    public function formatSize(?int $bytes): string
    {
        $bytes = max(0, (int) $bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        foreach ($units as $unit) {
            if ($bytes < 1024 || $unit === 'TB') {
                return round($bytes, 2) . ' ' . $unit;
            }

            $bytes /= 1024;
        }

        return '0 B';
    }

    public function buildFolderPath(int $folderId): ?string
    {
        if ($folderId === 0) {
            return null;
        }

        $segments = [];
        $current = MediaFolder::query()->find($folderId);
        $guard = 0;

        while ($current && $guard < 100) {
            $segment = trim((string) ($current->slug ?: Str::slug((string) $current->name)));

            if ($segment !== '') {
                array_unshift($segments, $segment);
            }

            if (! $current->parent_id || (int) $current->parent_id === 0) {
                break;
            }

            $current = MediaFolder::query()->find((int) $current->parent_id);
            $guard++;
        }

        return empty($segments) ? null : implode('/', $segments);
    }

    public function fileUrl(MediaFile $file): string
    {
        $url = (string) $file->url;

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/storage/')) {
            return $url;
        }

        if (str_starts_with($url, 'storage/')) {
            return asset($url);
        }

        return Storage::disk('public')->url($url);
    }
      public function copyLink()
    {
        if (! $this->selectedFileId) {
            return;
        }

        $file = MediaFile::withTrashed()->find($this->selectedFileId);
        if (! $file) {
            return;
        }

      $this->dispatch('media-copy-link', url: $this->fileUrl($file));

    }
};
?>

<div x-data>
    <div class="min-h-[calc(100vh-4rem)] bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100 flex flex-col">

    <div class="border-b border-slate-200 dark:border-slate-800 p-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <input id="media-upload-input" type="file" wire:model="upload" class="hidden" />

            <button type="button" onclick="document.getElementById('media-upload-input').click()" wire:loading.attr="disabled" wire:target="upload" class="bg-sky-600 hover:bg-sky-500 text-white px-3 py-2 flex items-center gap-2 rounded-lg text-sm font-medium transition">
              <flux:icon.upload />
              <span wire:loading.remove wire:target="upload">Upload</span>
              <span wire:loading wire:target="upload">Uploading...</span>
            </button>

            <flux:modal.trigger name="create-media-folder">
                <button type="button" x-on:click.prevent="$dispatch('open-modal', 'create-media-folder')" class="border border-slate-300 bg-white hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 px-3 py-2 rounded-lg text-sm transition">
                    <flux:icon.folder-create/>
                </button>
            </flux:modal.trigger>

            <button wire:click="setTypeFilter('all')" class="bg-blue-600 hover:bg-blue-600/60 px-4 py-2 flex items-center gap-2 rounded-lg text-sm text-white">
                <flux:icon.filter/>
                ( All )
            </button>

            <button wire:click="setTypeFilter('images')" class="bg-blue-600 hover:bg-blue-600/60 px-4 py-2 rounded-lg text-sm text-white">
                Images
            </button>

            <button wire:click="setTypeFilter('documents')" class="bg-blue-600 hover:bg-blue-600/60 px-4 py-2 rounded-lg text-sm text-white">
                Documents
            </button>
        </div>

        <div class="w-full md:w-72">
            <input type="text"
                placeholder="Search in current folder"
                wire:model.live.debounce.300ms="search"
                class="w-full border border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-800 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500" />
        </div>
    </div>

    <div class="border-b border-slate-200 dark:border-slate-800 p-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <button
                wire:click="goRoot"
                class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                x-on:dragover.prevent
                x-on:drop.prevent="
                    const fileId = $event.dataTransfer.getData('text/plain');
                    if (fileId) $wire.moveFileToRoot(parseInt(fileId));
                "
            >All media</button>

            @foreach ($this->breadcrumbs as $crumb)
                <span class="text-slate-400 dark:text-slate-500">/</span>
                <button wire:click="goToFolder({{ $crumb['id'] }})" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                    {{ $crumb['name'] }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="setSort('name_asc')" @class([
                'px-3 py-1.5 rounded-lg text-sm border transition',
                'bg-sky-600 text-white border-sky-600' => $sort === 'name_asc',
                'border-slate-300 bg-white hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700' => $sort !== 'name_asc',
            ])>
                Sort A-Z
            </button>

            <button wire:click="setSort('newest')" @class([
                'px-3 py-1.5 rounded-lg text-sm border transition',
                'bg-sky-600 text-white border-sky-600' => $sort === 'newest',
                'border-slate-300 bg-white hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700' => $sort !== 'newest',
            ])>
                Newest
            </button>

            <button wire:click="$set('viewMode', 'grid')" @class([
                'px-3 py-1.5 rounded-lg text-sm border transition',
                'bg-sky-600 text-white border-sky-600' => $viewMode === 'grid',
                'border-slate-300 bg-white hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700' => $viewMode !== 'grid',
            ])>
                Grid
            </button>

            <button wire:click="$set('viewMode', 'list')" @class([
                'px-3 py-1.5 rounded-lg text-sm border transition',
                'bg-sky-600 text-white border-sky-600' => $viewMode === 'list',
                'border-slate-300 bg-white hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700' => $viewMode !== 'list',
            ])>
                List
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden flex-col lg:flex-row">
        <div class="flex-1 p-4 sm:p-6 overflow-y-auto">
            @if ($viewMode === 'grid')
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6 gap-4 sm:gap-6">
                    @foreach ($this->folders as $folder)
                        <button
                            wire:dblclick="openFolder({{ $folder->id }})"
                            class="bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-xl p-4 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition text-left"
                            x-on:dragover.prevent
                            x-on:drop.prevent="
                                const fileId = $event.dataTransfer.getData('text/plain');
                                if (fileId) $wire.moveFileToFolder(parseInt(fileId), {{ $folder->id }});
                            "
                        >
                            <div class="h-24 flex items-center justify-center text-4xl">
                                <flux:icon.folder-git-2 class="size-12 text-yellow-400" />
                            </div>
                            <p class="text-center text-sm mt-2">{{ $folder->name }}</p>
                        </button>
                    @endforeach

                    @foreach ($this->files as $file)
                        <button
                            wire:click="selectFile({{ $file->id }})"
                            class="bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-xl overflow-hidden hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition text-left"
                            draggable="true"
                            x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $file->id }}')"
                        >
                            @if (str_starts_with((string) $file->mime_type, 'image/'))
                                <img src="{{ $this->fileUrl($file) }}" class="h-32 w-full object-cover" />
                            @else
                                <div class="h-32 w-full flex items-center justify-center bg-slate-50 dark:bg-slate-900/40">
                                    <flux:icon.book-open-text class="size-12 text-slate-400 dark:text-slate-500" />
                                </div>
                            @endif
                            <p class="text-center text-sm p-2 truncate">{{ $file->name }}</p>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($this->folders as $folder)
                        <button
                            wire:click="openFolder({{ $folder->id }})"
                            class="w-full bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg px-4 py-3 flex items-center justify-between"
                            x-on:dragover.prevent
                            x-on:drop.prevent="
                                const fileId = $event.dataTransfer.getData('text/plain');
                                if (fileId) $wire.moveFileToFolder(parseInt(fileId), {{ $folder->id }});
                            "
                        >
                            <span class="flex items-center gap-2"><flux:icon.folder-git-2 class="size-5 text-yellow-400" />{{ $folder->name }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Folder</span>
                        </button>
                    @endforeach
                    @foreach ($this->files as $file)
                        <button
                            wire:click="selectFile({{ $file->id }})"
                            class="w-full bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg px-4 py-3 flex items-center justify-between"
                            draggable="true"
                            x-on:dragstart="$event.dataTransfer.setData('text/plain', '{{ $file->id }}')"
                        >
                            <span class="truncate">{{ $file->name }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $this->formatSize($file->size) }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($this->folders->isEmpty() && $this->files->isEmpty())
                <div class="text-center text-slate-500 dark:text-slate-400 py-20">No media found in this folder.</div>
            @endif
        </div>

        <div class="w-full lg:w-80 border-t lg:border-t-0 lg:border-l border-slate-200 dark:border-slate-800 p-4 sm:p-6 block">
            @if ($this->selectedFile)
                <div class="h-56 sm:h-60 bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-xl flex items-center justify-center overflow-hidden">
                    @if (str_starts_with((string) $this->selectedFile->mime_type, 'image/'))
                        <img src="{{ $this->fileUrl($this->selectedFile) }}" class="h-full w-full object-cover" />
                    @else
                        <flux:icon.book-open-text class="size-16 text-slate-400 dark:text-slate-500" />
                    @endif
                </div>

                <div class="mt-6 space-y-2 overflow-y-auto text-sm text-slate-600 dark:text-slate-300">
                    <p>Name: </p>
                      <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->selectedFile->name }}"
                        />
                    <p>Alt text: </p>
                      <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->selectedFile->alt ?? '--' }}"
                        />
                    <p>Size: </p>
                      <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->formatSize($this->selectedFile->size) }}"
                        />
                        <p>Full url :</p>
                        <div class="flex items-center gap-1">
                    <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->fileUrl($this->selectedFile) }}"
                        />
                        <button type="button"
                                    wire:click="copyLink"
                                    class="px-2 py-1 border border-gray-200 dark:border-slate-700 rounded bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 cursor-pointer"
                                    title="Copy URL">
                                <flux:icon.copy/>
                            </button>
                      </div>
                    <p>Uploaded at</p>
                    <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->selectedFile->created_at->format('Y-m-d') }}"
                        />
                    <p>Modified at :</p>
                    <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->selectedFile->updated_at->format('Y-m-d') }}"
                        />
                    <p>Type :</p>
                    <input type="text" 
                           class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-[11px] bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-gray-100"
                          readonly
                          value="{{ $this->selectedFile->mime_type }}"
                        />
                        
                </div>
            @elseif ($this->selectedFolder)
                <div class="h-56 sm:h-60 bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-xl flex items-center justify-center">
                    <flux:icon.folder-git-2 class="size-16 text-yellow-400" />
                </div>

                <div class="mt-6 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <p>Folder: {{ $this->selectedFolder->name }}</p>
                    <p>Type: Folder</p>
                </div>
            @else
                <div class="h-56 sm:h-60 bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-xl flex items-center justify-center">
                    <span class="text-slate-500 dark:text-slate-400">Select a file or folder</span>
                </div>
            @endif
        </div>
    </div>

</div>

@include('pages.media.partials.create-folder') <!-- create folder model-->

</div>
@script
<script>
    Livewire.on('media-copy-link', ({ url }) => {
        navigator.clipboard.writeText(url)
            .then(() => {
                console.log('copied!');
            })
            .catch(err => {
                console.error('Copy failed', err);
            });
    });
</script>
@endscript
