<x-app-layout>
    <div class="h-full max-w-6xl min-w-6xl mx-auto pt-32">
        <h1 class="font-bold text-xl">Auftrag erstellen</h1>
        <form action="{{ route('orders.store') }}" method="POST" class="flex flex-col">
            @csrf()
            @if(session()->has('success'))
                <span class="text-sm font-semibold text-green-600">Auftrag erfolgreich erstellt</span>
            @endif
            <div class="flex flex-col mt-3">
                <label>Auftragsnummer</label>
                <input type="text" name="id" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] font-semibold" placeholder="Auftragsnummer" />
                @error('id') <span class="mt-0.5 text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex flex-col mt-2">
                <label>Produkt</label>
                <select name="mapping_id" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] font-semibold">
                    <option value="" disabled selected>Produkt auswählen...</option>
                    @foreach($mappings as $mapping)
                        <option value="{{ $mapping->id }}">{{ $mapping->product->name }}</option>
                    @endforeach
                </select>
                @error('mapping_id') <span class="mt-0.5 text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <button class="bg-[#0085CA] rounded-sm text-white font-semibold uppercase hover:bg-[#0085CA]/80 w-fit px-2 py-1 mt-2">Erstellen</button>
        </form>
    </div>
</x-app-layout>
