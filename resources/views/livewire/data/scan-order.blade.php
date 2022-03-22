<div class="h-full w-full flex flex-col justify-center items-center max-w-md mx-auto" x-data="{ order: '' }">
    <h1 class="text-4xl font-bold">Auftrag scannen</h1>
    <div class="flex w-full items-center mt-2 shadow-md">
        <div class="h-12" wire:loading>
            <div class="bg-white rounded-l-sm h-full flex items-center px-3"><i class="fal fa-sync animate-spin"></i></div>
        </div>
        <input type="text" x-model="order" @focus="focused = true" @keyup.enter="$wire.scanOrder(order)" class="h-12 font-semibold rounded-sm border-0 focus:ring-[#0085CA] grow" placeholder="Auftrag scannen oder eingeben..." autofocus>
    </div>
    @error('order') <span class="text-red-500 font-bold text-sm mt-2">{{ $message }}</span> @enderror
    <div class="flex flex-col bg-white w-full divide-y mt-2 divide-gray-200 shadow-md border border-gray-200">
        <div class="text-xs text-gray-500 px-2 py-0.5">{{ $orders->count() }} neuste Aufträge</div>
        @forelse($orders as $order)
            <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="px-2 py-1 hover:bg-gray-50 text-sm font-semibold flex flex-col">
                {{ $order->id }}
                <span class="text-xs font-normal text-gray-500">{{ $order->mapping->product->name }}</span>
            </a>
        @empty
            Test
        @endforelse
    </div>
</div>
