<div class="flex flex-col bg-gray-100 w-full h-full pt-28 z-[9] border-l border-gray-200">
    <div class="pl-8 pr-4 py-3 text-lg bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <i class="far fa-cog mr-2"></i>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="pl-8 pr-4 py-2 bg-white font-semibold flex gap-3 border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-calendar-circle-plus text-[#0085CA] mr-1"></i> Anmeldedatum</span>
            <span class="text-sm text-gray-500 mt-1">{{ $orderInfo->created_at != null ? $orderInfo->created_at->diffForHumans() : 'Unbekannt' }}</span>
        </div>
    </div>
    <div class="pl-8 pr-4 py-1 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-calendar-lines-pen text-[#0085CA] mr-1"></i> Letzte Änderung</span>
            <span class="text-sm text-gray-500 mt-1">{{ $orderInfo->updated_at != null ? $orderInfo->updated_at->diffForHumans() : 'Unbekannt' }}</span>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-link text-[#0085CA] mr-1"></i> Auftragsverlinkung</span>
            <span class="text-xs text-gray-500">Hier kann der Auftrag mit anderen aufträgen verlinkt werden um die Rückverfolgbarkeit noch weiter zu optimieren</span>
            <div class="flex gap-2 mt-4 items-center">
                @if(!empty($orders))
                    <div class="flex gap-1 items-center" x-data="{ showInput: false, order: '' }">
                        <a href="javascript:;" @click="showInput = true" class="px-2 py-1.5 bg-[#0085CA] text-white hover:bg-[#0085CA]/80 text-sm rounded-sm flex items-center" x-show="!showInput">
                            <i class="far fa-plus"></i>
                        </a>
                        <input type="text" x-model="order" @change="$wire.addLinkedOrder('', order)" class="bg-gray-200 rounded-sm border-0 font-semibold text-xs focus:ring-[#0085CA]" placeholder="Auftrag eingeben..." x-show="showInput"/>
                        <a href="javascript:;" @click="showInput = false" class="px-2 py-1.5 bg-red-500 text-white hover:bg-red-500/80 text-sm rounded-sm flex items-center" x-show="showInput">
                            <i class="far fa-times"></i>
                        </a>
                        <i class="fal fa-chevron-right ml-1"></i>
                    </div>
                @endif
                @forelse($orders as $order)
                    <span class="px-3 py-1 text-sm bg-gray-200 rounded-sm flex items-center">
                        {{ $order->id }} ({{ $order->mapping->product->name }})
                        @if($order->id != $orderId)
                            <a href="javascript:;" wire:click="removeLinkedOrder('{{ $order->id }}')" class="ml-2 text-red-500"><i class="far fa-times"></i></a>
                        @else
                            <span class="text-[#0085CA] ml-1">(Current)</span>
                        @endif
                    </span>
                    <div class="flex gap-1 items-center" x-data="{ showInput: false, order: '' }">
                        <i class="fal fa-chevron-right mr-1"></i>
                        <a href="javascript:;" @click="showInput = true" class="px-2 py-1.5 bg-[#0085CA] text-white hover:bg-[#0085CA]/80 text-sm rounded-sm flex items-center" x-show="!showInput">
                            <i class="far fa-plus"></i>
                        </a>
                        <input type="text" x-model="order" @change="$wire.addLinkedOrder('{{ $order->id }}', order)" class="bg-gray-200 rounded-sm border-0 font-semibold text-xs focus:ring-[#0085CA]" placeholder="Auftrag eingeben..." x-show="showInput"/>
                        <a href="javascript:;" @click="showInput = false" class="px-2 py-1.5 bg-red-500 text-white hover:bg-red-500/80 text-sm rounded-sm flex items-center" x-show="showInput">
                            <i class="far fa-times"></i>
                        </a>
                        @if(!$loop->last)
                            <i class="fal fa-chevron-right ml-1"></i>
                        @endif
                    </div>
                @empty
                    <a href="javascript:;" wire:click="addLink()" class="px-2 py-1.5 w-fit uppercase bg-[#0085CA] text-white hover:bg-[#0085CA]/80 text-sm rounded-sm flex items-center">
                        <i class="fal fa-link mr-1"></i> Verlinkung erstellen
                    </a>
                @endforelse
            </div>
            @error('order') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
            @if(!empty($orders))
                <a href="javascript:;" wire:click="removeLink()" class="px-2 py-1.5 mt-4 w-fit uppercase bg-yellow-400 hover:bg-yellow-400/80 text-sm rounded-sm flex items-center">
                    <i class="fal fa-link-slash mr-1"></i> Verlinkung löschen
                </a>
                <span class="text-xs text-gray-400 mt-2"><i class="fal fa-exclamation-triangle text-red-500 mr-1"></i> Achtung! Hier wird die komplette Verlinkung gelöscht! Die anderen Aufträge werden ebenfalls aus der Verlinkung entfernt.</span>
            @endif
        </div>
    </div>
    <div class="pl-8 pr-4 py-2 mt-2 bg-white font-semibold flex border-b border-t border-gray-200 items-center z-[8]">
        <span class="font-semibold text-lg text-red-500"><i class="fal fa-exclamation-triangle mr-1"></i> Gefahrenzone</span>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-sitemap text-orange-500 mr-1"></i> Mapping ändern (Auftragstyp)</span>
            <span class="text-xs text-gray-500">Ändert das zugewiesene Auftragstyp. Dies ändert die komplette Struktur und kann Daten durcheinanderbringen!</span>
            <div class="flex mt-4 gap-2" x-data="{ product: {{ $orderInfo->mapping->product_id }} }">
                <select x-model="product" class="bg-gray-100 rounded-sm font-semibold border-0 text-sm focus:ring-[#0085CA]">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->product->name }} @if($product->id == $orderInfo->mapping_id) (Current) @endif</option>
                    @endforeach
                </select>
                <button @click="$wire.changeProduct(product)" class="bg-orange-500 hover:bg-orange-500/80 uppercase text-white rounded-sm text-sm px-3"><i class="fal fa-pencil mr-0.5"></i> Ändern</button>
            </div>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-trash text-red-500 mr-1"></i> Alle Einträge löschen</span>
            <span class="text-xs text-gray-500">Hier können alle Einträge des Auftrags in allen Bearbeitungsschritten gelöscht werden. Diese aktion kann nicht rückgängig gemacht werde und ist deshalb mit Vorischt zu geniessen!</span>
            @if(session()->has('success')) <span class="text-xs text-green-600 mt-2">Daten erfolgreich gelöscht!</span> @endif
            <button wire:click="removeAllData" class="bg-red-500 hover:bg-red-500/80 uppercase text-white rounded-sm text-sm px-3 py-1.5 w-fit mt-3"><i class="far fa-exclamation-triangle mr-1"></i> Alle Einträge Löschen</button>
        </div>
    </div>

    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-trash text-red-500 mr-1"></i> Auftrag löschen</span>
            <span class="text-xs text-gray-500">Löscht den kompletten Auftrag und die damit verbundenen Daten</span>
            <button class="bg-red-500 hover:bg-red-500/80 uppercase text-white rounded-sm text-sm px-3 py-1.5 w-fit mt-3"><i class="far fa-exclamation-triangle mr-1"></i> Auftrag löschen</button>
        </div>
    </div>
</div>
