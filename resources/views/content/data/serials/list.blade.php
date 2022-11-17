<x-app-layout>
    <div class="h-full flex">
        <div class="bg-white flex flex-col max-w-sm min-w-sm w-full px-4 pt-4 gap-2 shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[8]">
            <a href="{{ route('serialise') }}" class="text-red-500 mb-1"><i class="fal fa-arrow-left mr-1"></i>Zurück zur Serialisation</a>
            <h1 class="font-semibold text-lg">Optics Balzers Serialization Scheme</h1>
            <div class="flex flex-col divide-y divide-gray-200 bg-gray-100 rounded-sm px-2 py-1">
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">PO:</span>
                    <span class="text-gray-600">{{ $po->po_cust }}</span>
                </div>
                <div class="flex justify-between w-full text-sm py-1">
                    <span class="font-semibold">Date:</span>
                    <span class="text-gray-600">{{ date('d.m.Y', strtotime($po->delivery_date)) }}</span>
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
        <div class="flex flex-col w-full divide-y divide-gray-200 bg-white overflow-y-auto">
            <div class="px-4 py-2">
                <h1 class="font-bold text-lg sticky">Positionen</h1>
            </div>
            <div class="flex flex-col divide-y divide-gray-200">
                @forelse($orders as $order)
                    <?php $missings = $order->missingSerials(); ?>
                    <div class="flex flex-col" x-data="{ open: false }">
                        <a href="javascript:;" class="flex w-full hover:bg-gray-50 px-4 py-2" @click="open = !open">
                            <i class="fal fa-chevron-right fa-fw mr-2 mt-1 shrink-0" x-show="!open"></i>
                            <i class="fal fa-chevron-down fa-fw mr-2 mt-1 shrink-0" x-show="open"></i>
                            <div class="flex gap-2 grow">
                                <span class="font-semibold">{{ $order->po_pos }}</span>
                                <span class="text-gray-500">({{ $order->id }})</span>
                                <span class="h-max bg-gray-100 rounded-sm px-2 whitespace-nowrap">{{ sizeof($order->serials) - $missings->count() }} / {{ sizeof($order->serials) }}</span>
                                <span class="h-max items-center bg-gray-100 rounded-sm px-2 whitespace-nowrap">{{ $order->serials->first()->id ?? '?' }} - {{ $order->serials->last()->id ?? '?' }}</span>
                                @if($missings->count() > 0)
                                    <span class="bg-red-500/20 rounded-sm px-2">{{ join(', ', $missings->pluck('id')->toArray()) }}</span>
                                @endif
                            </div>
                        </a>
                        <div class="flex flex-col pl-12 text-sm pb-1" x-show="open">
                            <div class="flex gap-1 mb-1">
                                <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="bg-[#0085CA] rounded-sm px-1 py-0.5 text-white hover:bg-[#0085CA]/80 w-fit"><i class="fal fa-link"></i> Zu diesem Auftrag springen</a>
                                <a href="javascript:;" class="bg-red-500 rounded-sm px-1 py-0.5 text-white hover:bg-red-500/80 w-fit"><i class="fal fa-unlink"></i> Verlinkung löschen</a>
                            </div>
                            <span><b>Ordered Qty:</b> {{ sizeof($order->serials) }}</span>
                            <span><b>Delivered Qty:</b> {{ sizeof($order->serials) - $missings->count() }}</span>
                            <span><b>Missing Serials:</b> {{ join(', ', $missings->pluck('id')->toArray()) }}</span>
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
