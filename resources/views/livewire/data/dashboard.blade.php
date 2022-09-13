<div class="w-full h-full flex" x-data="{ mode: $persist(@entangle('mode')), type: $persist(@entangle('searchType').defer) }">
    <div class="flex flex-col pb-28 bg-white h-full max-w-sm min-w-sm w-full shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] left-0 divide-y divide-gray-300 z-[8]">
        <a href="javascript:;" @click="mode = 1" class="flex flex-col px-4 py-4 hover:bg-gray-200 bg-gray-100">
            <h1 class="text-xl font-bold" :class="mode == 1 ? 'text-[#0085CA]' : ''">Auftragsmodus</h1>
            <span class="text-sm text-gray-600">Öffne oder durchsuche Aufträge</span>
        </a>
        <div class="flex flex-col divide-y divide-gray-200 overflow-y-auto" x-show="mode == 1">
            <div class="pl-6 py-0.5 bg-gray-100 sticky top-0 border-b border-gray-200 bg-gray-100">
                <span class="text-sm font-bold">Letzte {{ $orders->count() }} Aufträge</span>
            </div>
            @foreach($orders as $order)
                <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="hover:bg-gray-50 flex flex-col text-sm pl-6 py-1">
                    <span class="font-semibold">{{ $order->id }}</span>
                    <span class="text-xs text-gray-600">{{ $order->mapping->product->name }}</span>
                </a>
            @endforeach
        </div>
        <a href="javascript:;" @click="mode = 2" class="flex flex-col px-4 py-4 hover:bg-gray-200 bg-gray-100">
            <h1 class="text-xl font-bold" :class="mode == 2 ? 'text-[#0085CA]' : ''">Rückverfolgungsmodus</h1>
            <span class="text-sm text-gray-600">Suche nach Wafern und schau Ihren Verlauf an</span>
        </a>
        <div class="flex flex-col divide-y divide-gray-200 overflow-y-auto" x-show="mode == 2">
            <div class="pl-6 py-0.5 bg-gray-100 sticky top-0 border-b border-gray-200 bg-gray-100">
                <span class="text-sm font-bold">Letzte {{ $wafers->count() }} Wafer</span>
            </div>
            @foreach($wafers as $wafer)
                <a href="{{ route('wafer.show', ['wafer' => $wafer->id]) }}" class="hover:bg-gray-50 flex flex-col text-sm pl-6 py-1">
                    <span class="font-semibold">{{ $wafer->id }}</span>
                    @if($wafer->rejected && !$wafer->reworked)
                        <span class="text-xs text-red-500">{{ $wafer->rejection_reason }} in {{ $wafer->rejection_order }}</span>
                    @elseif($wafer->reworked)
                        <span class="text-xs text-orange-500">Nacharbeit</span>
                    @else
                        <span class="text-xs text-green-600">Wafer in Ordnung</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
    <div class="flex flex-col bg-white h-full w-full z-[7] overflow-y-auto" x-data="{ wafer: @entangle('search') }" x-show="mode == 1">
        <div class="flex flex-col bg-white px-4 py-3">
            <h1 class="text-xl font-bold"><i class="far fa-search mr-2"></i> Aufträge durchsuchen</h1>
            <span class="text-sm text-gray-500">Gib oder scanne eine Auftragsnummer ein. Falls die Eingabe einen direkten Treffer landet wird man umgeleitet</span>
        </div>
        <div class="flex w-full bg-white border-t border-gray-200 shadow-md z-[7] sticky top-0" >
            <a href="javascript:;" x-show="wafer != ''" @click="wafer = ''" class="h-full flex items-center bg-gray-200 hover:bg-gray-100 px-3"><i class="fal fa-times"></i></a>
            <input type="text" x-model.lazy="wafer" autofocus class="w-full border-0 font-semibold grow px-4 text-lg text-gray-600" x-trap="wafer != ''" placeholder="Auftrag einscannen oder eingeben"/>
            <a href="javascript:;" wire:click="$refresh" class="h-full flex items-center bg-gray-200 px-2 hover:bg-gray-100 font-semibold">Suchen</a>
        </div>
        <div class="flex flex-col h-full divide-y divide-gray-200 relative z-[6]">
            <div class="w-full h-full absolute" wire:loading>
                <div class="w-full h-full flex justify-center absolute items-center z-[5]">
                    <h1 class="text-[#0085CA] font-bold text-2xl"><i class="far fa-spinner animate-spin"></i> Wafer werden geladen...</h1>
                </div>
                <div class="w-full h-full bg-white opacity-60 absolute z-[4]"></div>
            </div>
            <div class="bg-gray-200 py-1 px-4">
                <span class="text-sm text-gray-600">{{ sizeof($foundOrders) }} Aufträge gefunden</span>
            </div>
            @foreach($foundOrders as $order)
                <a href="{{ route('orders.show', ['order' => $order->id]) }}" class="hover:bg-gray-50 flex flex-col px-6 py-2">
                    <span class="text-sm font-semibold">{{ $order->id }}</span>
                    <span class="text-xs text-gray-500">{{ $order->mapping->product->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
    <div class="flex flex-col bg-white h-full w-full pt-28 z-[7] overflow-y-auto" x-data="{ wafer: @entangle('search') }" x-show="mode == 2">
        <div class="flex flex-col bg-white px-4 py-3">
            <h1 class="text-xl font-bold"><i class="far fa-search mr-2"></i> Wafer durchsuchen</h1>
            <span class="text-sm text-gray-500">Gib oder scanne eine Wafer ID ein. Falls die Eingabe einen direkten Treffer landet wird man umgeleitet</span>
        </div>
        <div class="flex w-full bg-white border-t border-gray-200 items-center">
            <span class="font-semibold text-lg w-fit px-4 whitespace-nowrap">Suchen nach:</span>
            <select x-model="type" class="focus:ring-0 bg-white w-full border-0 font-semibold text-lg">
                <option value="wafer">Wafer ID</option>
                <option value="serial">Seriennummer</option>
                <option value="box">Box</option>
                <option value="chrome">Chromcharge</option>
                <option value="ar">AR Charge</option>
            </select>
        </div>
        <div class="flex w-full bg-white border-t border-gray-200 shadow-md z-[7] sticky top-0" >
            <a href="javascript:;" x-show="wafer != ''" @click="wafer = ''" class="h-full flex items-center bg-gray-200 hover:bg-gray-100 px-3"><i class="fal fa-times"></i></a>
            <input type="text" x-model.lazy="wafer" autofocus class="w-full border-0 font-semibold grow px-4 text-lg text-gray-600" x-trap="wafer != ''" placeholder="Wafer einscannen oder eingeben"/>
            <a href="javascript:;" wire:click="$refresh" class="h-full flex items-center bg-gray-200 px-2 hover:bg-gray-100 font-semibold">Suchen</a>
        </div>
        <div class="flex flex-col h-full divide-y divide-gray-200 z-[6] relative">
            <div class="w-full h-full absolute" wire:loading>
                <div class="w-full h-full flex justify-center absolute items-center z-[5]">
                    <h1 class="text-[#0085CA] font-bold text-2xl"><i class="far fa-spinner animate-spin"></i> Wafer werden geladen...</h1>
                </div>
                <div class="w-full h-full bg-white opacity-60 absolute z-[4]"></div>
            </div>
            <div class="bg-gray-200 py-1 px-4">
                <span class="text-sm text-gray-600">{{ sizeof($foundWafers) }} Wafer gefunden</span>
            </div>
            @if($searchType == 'wafer' || $searchType == 'box' || $searchType == 'chrome' || $searchType = 'ar')
                @foreach($foundWafers as $wafer)
                    <a href="{{ route('wafer.show', ['wafer' => $wafer->id]) }}" class="hover:bg-gray-50 flex flex-col px-6 py-2">
                        <span class="text-sm font-semibold">{{ $wafer->id }}</span>
                        @if($wafer->rejected && !$wafer->reworked)
                            <span class="text-xs text-red-500">{{ $wafer->rejection_reason }} in {{ $wafer->rejection_order }}</span>
                        @elseif($wafer->reworked)
                            <span class="text-xs text-orange-500">Nacharbeit</span>
                        @else
                            <span class="text-xs text-green-600">Wafer in Ordnung</span>
                        @endif
                    </a>
                @endforeach
            @elseif($searchType == 'serial')
                @foreach($foundWafers as $wafer)
                    <a href="{{ route('wafer.show', ['wafer' => $wafer->wafer_id]) }}" class="hover:bg-gray-50 flex flex-col px-6 py-2">
                        <span class="text-sm font-semibold">{{ $wafer->wafer_id }} <span class="text-gray-500">({{ $wafer->id }})</span></span>
                        @if($wafer->wafer->rejected && !$wafer->wafer->reworked)
                            <span class="text-xs text-red-500">{{ $wafer->wafer->rejection_reason }} in {{ $wafer->wafer->rejection_order }}</span>
                        @elseif($wafer->wafer->reworked)
                            <span class="text-xs text-orange-500">Nacharbeit</span>
                        @else
                            <span class="text-xs text-green-600">Wafer in Ordnung</span>
                        @endif
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>
