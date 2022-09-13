<div class="flex flex-col bg-white w-full h-full max-w-xs min-w-xs z-[10] pb-40 shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] overflow-y-auto" x-data="{ openInfo: false }">
    <div class="px-4 py-3 text-xl font-bold flex bg-white justify-between items-center border-b border-gray-200 sticky top-0">
        <div class="flex grow flex-col">
            {{ $order->id }}
            <span class="font-normal text-sm">{{ $order->mapping->product->name }}</span>
            <span class="font-normal text-xs text-gray-600">Artikel {{ $order->article }} | Kunde {{ $order->customer }}</span>
            @if($order->po != '')
                <a class="font-normal text-xs text-[#0085CA]" href="{{ route('serialise.list', ['po' => $order->po]) }}">In Serialisierungsliste anzeigen <i class="fal fa-link"></i></a>
            @endif
        </div>
        <a href="javascript:;" @click="openInfo = !openInfo" class="p-2 rounded-sm hover:bg-gray-100"><i class="far fa-clock-rotate-left text-[#0085CA]"></i></a>
    </div>
    <div class="flex flex-col divide-y divide-gray-200" x-show="openInfo">
        <div class="px-4 py-2 flex justify-between">
            <span class="font-semibold"><i class="far fa-clock-rotate-left mr-1"></i> Auftragshistorie</span>
            <a href="javascript:;" @click="openInfo = false" class="rounded-sm hover:bg-gray-100 px-2"><i class="far fa-times"></i></a>
        </div>
        @forelse($orders as $o)
            <a href="{{ route('orders.show', ['order' => $o->id]) }}" class="flex pl-4 items-center justify-between py-2 @if($o->id == $orderId) bg-gray-100 @endif hover:bg-gray-50">
                <div class="flex flex-col grow">
                    <span class="text-base font-semibold @if($o->id == $orderId) text-[#0085CA] @endif">{{ $o->id }} @if($o->id == $orderId) (Current) @endif</span>
                    <span class="text-xs text-gray-500">{{ $o->mapping->product->name }}</span>
                </div>
                @if($o->id == $orderId)
                    <i class="far fa-chevron-left animate-pulse pr-4"></i>
                @endif
            </a>
        @empty
        @endforelse
    </div>
    <div class="flex flex-col divide-y divide-gray-200" x-show="!openInfo">
        @forelse($blocks as $block)
            @if(isset($block->type))
                <div class="px-4 py-1.5 font-extrabold bg-gray-100">
                    <i class="{{ $block->icon }} fa-fw mr-1"></i> {{ $block->value }}
                </div>
            @else
                <a href="{{ route('blocks.show', ['order' => $order->id, 'block' => $block->identifier]) }}" class="flex pl-4 items-center justify-between py-2 @if($block->id == $this->blockId) bg-gray-50 @endif hover:bg-gray-50">
                    @if($block->icon != '')
                        <span class="text-lg font-bold mr-3 @if($block->id == $this->blockId) text-[#0085CA] @endif"><i class="far fa-fw {{ $block->icon }}"></i></span>
                    @else
                        <span class="text-lg font-bold mr-3 @if($block->id == $this->blockId) text-[#0085CA] @endif">{{ sprintf('%02d', $block->avo) }}</span>
                    @endif
                    <div class="flex flex-col grow">
                        <span class="text-base font-semibold @if($block->id == $this->blockId) text-[#0085CA] @endif">{{ $block->name }} @if($block->admin_only) <i class="far fa-lock ml-0.5"></i> @endif</span>
                        <span class="text-xs text-gray-500">{{ $block->description ?? 'Keine Beschreibung...' }}</span>
                    </div>
                    @if($block->id == $this->blockId)
                        <i class="far fa-chevron-left animate-pulse pr-4"></i>
                    @endif
                </a>
            @endif
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
