<div class="h-full max-w-3xl min-w-3xl mx-auto pt-4 pb-4 w-full overflow-y-auto">
    <input type="text" placeholder="Auftrag suchen" wire:model.lazy="search" class="bg-gray-200 w-full rounded-md text-sm font-semibold focus:ring-0 border-none"/>
    <div class="flex flex-col bg-white rounded-md divide-y divide-gray-100 mt-2">
        @forelse($orders as $order)
            <a href="{{ route('coa.show', ['order' => $order->id]) }}" class="px-2 py-1 hover:bg-gray-50 text-sm font-semibold flex flex-col">
                {{ $order->id }}
                <span class="text-xs font-normal text-gray-400">{{ $order->article }}</span>
            </a>
        @empty
        @endforelse
    </div>
</div>
