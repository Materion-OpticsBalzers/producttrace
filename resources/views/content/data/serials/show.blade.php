<x-app-layout>
    <div class="w-full h-full flex flex-col px-8 mx-auto pt-32">
        <h1 class="font-bold text-xl">{{ $order->id }}</h1>
        <span class="text-sm text-gray-500">Wähle die Serials aus denen du eine AB zuweisen willst</span>
        <div class="flex gap-1 items-center mt-2">
            <input type="text" placeholder="POS" class="bg-white border-0 font-semibold rounded-sm focus:ring-[#0085CA]" />
            <input type="text" placeholder="AB" class="bg-white border-0 font-semibold rounded-sm focus:ring-[#0085CA]" />
            <button onclick="" class="uppercase px-2 py-2 bg-[#0085CA] text-white font-semibold rounded-sm text-sm hover:bg-[#0085CA]/80">Zuweisen</button>
        </div>
        <div class="mt-2 flex flex-col divide-y divide-gray-200" x-data="{ serials: [] }">
            @forelse($serials as $serial)
                <label class="cursor-pointer hover:bg-gray-50 px-2 flex py-1 font-semibold bg-white items-center gap-2">
                    <input type="checkbox" value="{{ $serial->id }}" x-model="serials" class="text-[#0085CA] focus:ring-[#0085CA] rounded-sm"/>
                    <div class="flex flex-col">
                        {{ $serial->id }}
                        <span class="text-xs text-gray-500">{{ $serial->wafer_id }}</span>
                    </div>
                </label>
            @empty
            @endforelse
        </div>
    </div>
</x-app-layout>
