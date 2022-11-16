<div class="flex flex-col h-full max-w-5xl min-w-5xl mx-auto gap-1">
    <h1 class="font-bold text-xl mb-4">Benutzerverwaltung</h1>
    <input type="text" wire:model.lazy="search" class="mb-4 shadow-sm border-none" placeholder="Benutzer suchen..."/>
    @foreach($users as $user)
        <div class="grid grid-cols-4 bg-white shadow-sm p-2">
            <span class="font-semibold">{{ $user->personnel_number }}</span>
            <span class="font-semibold">{{ $user->name }}</span>
            <span>{{ $user->email }}</span>
            <div class="flex gap-4 justify-end">
                <label>
                    Admin
                    <input type="checkbox" wire:change="toggleAdmin({{ $user->id }})" value="true" @if($user->is_admin) checked @endif/>
                </label>
                <a href="javascript:" wire:click="removeSession({{ $user->id }})" class="text-red-500"><i class="fal fa-trash"></i></a>
            </div>
        </div>
    @endforeach
</div>
