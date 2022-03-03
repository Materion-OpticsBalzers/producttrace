<div class="flex flex-col bg-white w-full h-full max-w-md min-w-md pt-28 z-[9]">
    <div class="pl-8 py-3 text-xl font-bold flex flex-col border-b border-gray-200">
        {{ $order->id }}
        <span class="font-normal text-sm">{{ $order->mapping->product->name }}</span>
    </div>
    <div class="flex flex-col divide-y divide-gray-200">
        @if(!empty($blocks))
            @foreach($blocks as $block)
                <a href="{{ route('blocks.show', ['order' => $order->id, 'block' => $block->id]) }}" class="flex pl-8 items-center py-2 hover:bg-gray-50">
                    <span class="text-lg font-bold mr-3">{{ $block->avo }}</span>
                    <div class="flex flex-col">
                        <span class="text-base font-semibold">{{ $block->name }}</span>
                        <span class="text-xs text-gray-500">Beschreibung</span>
                    </div>
                </a>
            @endforeach
        @else
            <div class="flex pl-8 items-center py-2">
                <div class="flex flex-col">
                    <span class="text-base font-semibold text-red-500">Keine Blöcke für diesen Auftrag definiert</span>
                    <span class="text-xs text-gray-500">Für diesen auftrag würden noch keine arbeitsschritte definiert.</span>
                </div>
            </div>
        @endif
    </div>
</div>
