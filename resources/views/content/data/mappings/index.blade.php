<x-app-layout>
    <div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 w-full">
        <h1 class="font-bold text-xl">Produkte verwalten</h1>
        <form action="{{ route('mappings.store') }}" class="flex flex-col gap-2" method="POST">
            @csrf()
            <div class="flex">
                <input type="text" name="name" placeholder="Produkt hinzufügen..." class="bg-white shaodw-sm rounded-sm border-0 font-semibold focus:ring-[#0085CA]" />
                <button type="submit" class="bg-[#0085CA] px-2 font-semibold text-white text-sm hover:bg-[#0085CA]/80">Hinzufügen</button>
            </div>
            @error('name') <span class="text-xs mt-0.5 text-red-500">{{ $message }}</span> @enderror
        </form>
        <div class="flex flex-col gap-1 mt-2">
            @foreach($mappings as $mapping)
                <div class="flex bg-white shadow-sm px-3 py-1 justify-between rounded-sm hover:bg-gray-50">
                    <span class="font-semibold">{{ $mapping->product->name }}</span>
                    <div class="flex gap-2">
                        <a href="{{ route('mappings.show', ['mapping' => $mapping->id]) }}"><i class="fal fa-pencil"></i></a>
                        <form action="{{ route('mappings.destroy', ['mapping' => $mapping->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="javascript:;" onclick="this.closest('form').submit()" class="text-red-500"><i class="fal fa-trash"></i></a>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
