<x-app-layout>
    <div class="h-full max-w-6xl min-w-6xl mx-auto pt-32">
        <h1 class="font-bold text-xl">Produkte verwalten</h1>
        <div class="flex flex-col gap-1">
            @foreach($mappings as $mapping)
                <a href="{{ route('mappings.show', ['mapping' => $mapping->id]) }}" class="flex bg-white shadow-sm px-3 py-1 rounded-sm hover:bg-gray-50">
                    <span class="font-semibold">{{ $mapping->product->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
