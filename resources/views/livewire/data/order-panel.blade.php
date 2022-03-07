<div class="flex flex-col bg-white w-full h-full max-w-xs min-w-xs pt-28 z-[10] shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)]">
    <div class="px-4 py-3 text-xl font-bold flex justify-between items-center border-b border-gray-200">
        <div class="flex grow flex-col">
            {{ $order->id }}
            <span class="font-normal text-sm">{{ $order->mapping->product->name }}</span>
        </div>
    </div>
    <div class="flex flex-col divide-y divide-gray-200">
        @forelse($blocks as $block)
            <a href="{{ route('blocks.show', ['order' => $order->id, 'block' => $block->identifier]) }}" class="flex pl-8 items-center justify-between py-2 @if($block->id == $this->blockId) bg-gray-100 @endif hover:bg-gray-50">
                <span class="text-lg font-bold mr-3 @if($block->id == $this->blockId) text-[#0085CA] @endif">{{ sprintf('%02d', $block->avo) }}</span>
                <div class="flex flex-col grow">
                    <span class="text-base font-semibold @if($block->id == $this->blockId) text-[#0085CA] @endif">{{ $block->name }}</span>
                    <span class="text-xs text-gray-500">{{ $block->description ?? 'Keine Beschreibung...' }}</span>
                </div>
                @if($block->id == $this->blockId)
                    <i class="far fa-wave-pulse animate-pulse pr-4"></i>
                @endif
            </a>
        @empty
            <div class="flex pl-8 items-center py-2">
                <div class="flex flex-col">
                    <span class="text-base font-semibold text-red-500">Keine Blöcke für diesen Auftrag definiert</span>
                    <span class="text-xs text-gray-500">Für diesen auftrag würden noch keine arbeitsschritte definiert.</span>
                </div>
            </div>
        @endforelse
    </div>
</div>
