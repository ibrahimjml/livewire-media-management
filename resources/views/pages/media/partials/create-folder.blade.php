<flux:modal name="create-media-folder" class="max-w-md">
    <form wire:submit="createFolder" class="space-y-4 p-2">
        <div>
            <flux:heading size="lg">Create Folder</flux:heading>
            <flux:subheading>Enter a folder name.</flux:subheading>
        </div>

        <flux:input wire:model="newFolderName" label="Folder Name" type="text" placeholder="news" />

        @error('newFolderName')
            <p class="text-sm text-red-400">{{ $message }}</p>
        @enderror

        <div class="flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="filled" type="button">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="submit">Create</flux:button>
        </div>
    </form>
</flux:modal>