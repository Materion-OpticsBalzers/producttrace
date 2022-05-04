<x-app-layout>
    <div class="h-full flex">
        <div class="bg-white flex flex-col max-w-sm min-w-sm w-full pt-32 px-4 gap-2 shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[8]">
            <a href="{{ route('serialise') }}" class="text-red-500 mb-1"><i class="fal fa-arrow-left mr-1"></i>Zurück zur Serialisation</a>
            <h1 class="font-semibold text-lg">Optics Balzers Serialization Scheme</h1>
            <div class="flex flex-col divide-y divide-gray-200 bg-gray-100 rounded-sm px-2 py-1">
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">PO:</span>
                    <span class="text-gray-600">?</span>
                </div>
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">Date:</span>
                    <span class="text-gray-600">{{ date('d.m.Y', strtotime($po->created_at)) }}</span>
                </div>
                <div class="flex justify-between text-sm w-full py-1">
                    <span class="font-semibold">SO BZ:</span>
                    <span class="text-gray-600 font-bold">{{ $po->id }}</span>
                </div>
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">PN BZ:</span>
                    <span class="text-gray-600">{{ $po->article }}</span>
                </div>
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">PN:</span>
                    <span class="text-gray-600">{{ $po->article_cust }}</span>
                </div>
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">Format:</span>
                    <span class="text-gray-600">{{ $po->format }}</span>
                </div>
            </div>
            <div class="grow"></div>
            @if(session()->has('success'))
                <span class="py-2 text-green-600 text-sm">Erfolgreich exportiert</span>
            @endif
            <form method="POST" class="mb-4" action="{{ route('serialise.generate', ['po' => $po->id]) }}">
                @csrf()
                <button class="bg-green-600 rounded-sm text-sm text-white font-semibold w-full h-8 uppercase px-3 py-1 hover:bg-green-600/80">Liste in Excel Exportieren</button>
            </form>
        </div>
        <div class="flex flex-col pt-28 divide-y divide-gray-200 bg-white overflow-y-auto">
            <div class="grid grid-cols-5 bg-gray-100 px-3 py-2">
                <span class="font-bold">Line Item #</span>
                <span class="font-bold">Serial Block</span>
                <span class="font-bold">Ordered Qty</span>
                <span class="font-bold">Delivered Qty</span>
                <span class="font-bold">Missing</span>
            </div>
            @forelse($orders as $order)
                <?php $missings = $order->missingSerials(); ?>
                <div class="grid grid-cols-5 px-3 py-0.5 items-center text-sm">
                    <div class="flex">
                        <a href="javascript.;" class="text-red-500 mr-2"><i class="fal fa-unlink"></i></a>
                        <span class="font-semibold">{{ $order->po_pos }} <span class="text-gray-500">({{ $order->id }})</span></span>
                    </div>
                    <span class="text-gray-600">{{ $order->serials->first()->id ?? '?' }} - {{ $order->serials->last()->id ?? '?' }}</span>
                    <span class="text-gray-600">{{ sizeof($order->serials) }}</span>
                    <span class="text-gray-600">{{ sizeof($order->serials) - $missings->count() }}</span>
                    <p class="text-gray-600 text-center text-sm">{{ join(', ', $missings->pluck('id')->toArray()) }}</p>
                </div>
            @empty
            @endforelse
        </div>

    </div>
</x-app-layout>
