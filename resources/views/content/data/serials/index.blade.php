<x-app-layout>
    <div class="w-full h-full flex flex-col px-8 mx-auto pt-32">
        <h1 class="font-bold text-xl">Serialisierung</h1>
        <span class="text-sm text-gray-500">Wähle den auftrag aus den du Serialisieren möchtest</span>
        <form action="{{ route('serialise.search') }}" method="POST">
            @csrf()
            <input type="text" value="{{ $search ?? '' }}" name="search" onchange="this.closest('form').submit()" class="mt-2 rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-white shadow-sm" placeholder="Auftrag suchen..." />
        </form>
        <div class="mt-2 flex flex-col gap-1">
            @forelse($orders as $order)
                <div class="bg-white flex flex-col px-2 py-2 shadow-sm">
                    <span class="font-semibold flex items-center">{{ $order->id }} <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="text-[#0085CA] text-xs ml-1"><i class="fal fa-link"></i> Auftrag ansehen</a></span>
                    <span class="text-gray-600 text-sm">Serials: {{ $order->serials->first()->id }} - {{ $order->serials->last()->id }} ({{ $order->serials->count() }})</span>
                    <form action="{{ route('serialise.store', ['order' => $order->id]) }}" method="POST" class="flex items-center gap-2 mt-1">
                        @csrf()
                        <input type="text" name="po" value="{{ $order->po }}" class="rounded-sm text-xs border-0 focus:ring-[#0085CA] font-semibold bg-gray-200" placeholder="AB">
                        <input type="text" name="po_pos" value="{{ $order->po_pos }}" class="rounded-sm text-xs border-0 focus:ring-[#0085CA] font-semibold bg-gray-200" placeholder="POS">
                        @if($order->po == '')
                            <button type="submit" class="bg-[#0085CA] rounded-sm font-semibold text-white uppercase text-xs h-full px-2 hover:bg-[#0085CA]/80">Setzen</button>
                        @else
                            <button type="submit" class="bg-orange-500 rounded-sm font-semibold text-white uppercase text-xs h-full px-2 hover:bg-orange-500/80">Ändern</button>
                        @endif
                    </form>

                    @error('po') <span class="text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                    @error('po_pos') <span class="text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                </div>
            @empty
            @endforelse
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
