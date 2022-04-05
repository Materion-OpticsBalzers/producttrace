<x-app-layout>
    <div class="w-full h-full flex flex-col px-8 mx-auto pt-32">
        <h1 class="font-bold text-xl">Serialisierung</h1>
        <span class="text-sm text-gray-500">Wähle den auftrag aus den du Serialisieren möchtest</span>
        <div class="mt-2 flex flex-col gap-1">
            @forelse($orders as $order)
                <a href="{{ route('serialise.order', ['order' => $order->id]) }}" class="bg-white px-2 py-2 shadow-sm hover:bg-gray-50">
                    <span class="font-semibold">{{ $order->id }}</span>
                    <span class="text-sm text-gray-500 text-xs ml-2">Kompatibel mit serialisierung</span>
                </a>
            @empty
            @endforelse
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
