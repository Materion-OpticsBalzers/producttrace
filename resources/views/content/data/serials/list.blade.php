<x-app-layout>
    <div class="max-w-7xl min-w-7xl mx-auto h-full flex flex-col mx-auto pt-32">
        <div class="flex justify-between">
            <h1 class="font-bold text-xl mb-2">Serialisierungsliste ({{ $po->id }})</h1>
            @if(session()->has('success'))
                <span class="py-2 text-green-600 text-sm">Erfolgreich exportiert</span>
            @endif
            <form method="POST" action="{{ route('serialise.generate', ['po' => $po->id]) }}">
                @csrf()
                <button class="bg-[#0085CA] rounded-sm text-sm text-white font-semibold uppercase px-3 py-1 hover:bg-[#0085CA]/80">In Excel Exportieren</button>
            </form>
        </div>
        <span>PO BZ: {{ $po->id }}</span>
        <span>PN BZ: {{ $po->article }}</span>
        <span>Date: {{ $po->created_at }}</span>
        <span class="mt-1">PN: {{ $po->article_cust }}</span>
        <span>Format: {{ $po->format }}</span>
        <div class="bg-white p-2 flex flex-col mt-2 divide-y divide-gray-200">
            <div class="grid grid-cols-6 py-1">
                <span class="font-bold">Line Item</span>
                <span class="font-bold">From</span>
                <span class="font-bold">To</span>
                <span class="font-bold">Ordered Qty</span>
                <span class="font-bold">Delivered Qty</span>
                <span class="font-bold">Missing</span>
            </div>
            @forelse($orders as $order)
                <?php $missings = $order->missingSerials(); ?>
                <div class="grid grid-cols-6 py-0.5">
                    <span class="font-semibold">{{ $order->po_pos }} <span class="text-gray-500">({{ $order->id }})</span></span>
                    <span class="text-gray-600">{{ $order->serials->first()->id ?? '?' }}</span>
                    <span class="text-gray-600">{{ $order->serials->last()->id ?? '?' }}</span>
                    <span class="text-gray-600">{{ sizeof($order->serials) }}</span>
                    <span class="text-gray-600">{{ sizeof($order->serials) - $missings->count() }}</span>
                    <p class="text-gray-600 text-center text-sm">{{ join(', ', $missings->pluck('id')->toArray()) }}</p>
                </div>
            @empty
            @endforelse
        </div>
    </div>
</x-app-layout>
