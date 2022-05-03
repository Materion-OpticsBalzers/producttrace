<x-app-layout>
    <div class="max-w-6xl min-w-6xl mx-auto h-full flex flex-col mx-auto pt-32">
        <div class="flex justify-between">
            <h1 class="font-bold text-xl mb-2">Serialisierungsliste ({{ $po->id }})</h1>
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
        <div class="bg-white p-2 flex flex-col mt-2 gap-2 divide-y divide-gray-200">
            <div class="grid grid-cols-5 py-1">
                <span class="font-bold">Line Item</span>
                <span class="font-bold">From</span>
                <span class="font-bold">To</span>
                <span class="font-bold">Ordered Qty</span>
                <span class="font-bold">Delivered Qty</span>
            </div>
            @forelse($orders as $order)
                <?php $missings = $order->missingSerials(); ?>
                <div class="flex flex-col py-1">
                    <div class="grid grid-cols-5">
                        <span class="font-semibold">{{ $order->po_pos }} <span class="text-gray-500">({{ $order->id }})</span></span>
                        <span class="text-gray-600">{{ $order->serials->first()->id ?? '' }}</span>
                        <span class="text-gray-600">{{ $order->serials->last()->id ?? '' }}</span>
                        <span class="text-gray-600">{{ sizeof($order->serials) }}</span>
                        <span class="text-gray-600">{{ sizeof($order->serials) - $missings->count() }}</span>
                    </div>
                    <span class="text-gray-400 text-xs">Missing serials:</span>
                    <span class="text-red-500/70 text-xs">{{ join(', ', $missings->pluck('id')->toArray()) }}</span>
                </div>
            @empty
            @endforelse
        </div>
    </div>
</x-app-layout>
