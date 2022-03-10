<div class="flex flex-col bg-gray-100 w-full h-full pt-28 z-[9] border-l border-gray-200">
    <div class="pl-8 pr-4 py-3 text-lg bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <i class="far fa-cog mr-2"></i>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="far fa-clock-rotate-left text-[#0085CA] mr-1"></i> Auftrag verlinken</span>
            <span class="text-xs text-gray-500">Hier kann der Auftrag mit anderen aufträgen verlinkt werden um die Rückverfolgbarkeit noch weiter zu optimieren</span>
            <div class="flex gap-2 mt-4 items-center">
                @forelse($orders as $order)
                    <span class="px-3 py-1 text-sm bg-gray-200 rounded-sm flex items-center">{{ $order->id }} <a href="javascript:;" class="ml-2 text-red-500"><i class="fal fa-times"></i></a></span>
                    @if(!$loop->last)
                        <i class="fal fa-chevron-right"></i>
                    @endif
                @empty
                @endforelse
            </div>
            <div class="flex mt-4 gap-2">
                <input type="text" placeholder="Auftrag eingeben..." class="bg-gray-100 rounded-sm text-sm font-semibold border-0 focus:ring-[#0085CA]"/>
                <button class="bg-[#0085CA] hover:bg-[#0085CA]/80 uppercase text-white rounded-sm text-sm px-3">Hinzufügen</button>
            </div>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="far fa-sitemap text-[#0085CA] mr-1"></i> Produkt ändern</span>
            <span class="text-xs text-gray-500">Ändert das zugewiesene Produkt. Dies ändert die komplette Struktur und kann Daten durcheinanderbringen!</span>
            <div class="flex mt-4 gap-2">
                <select class="bg-gray-100 rounded-sm font-semibold border-0 text-sm focus:ring-[#0085CA]">
                    @foreach($products as $product)
                        <option @if($orderInfo->mapping->product_id == $product->id) selected  @endif value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <button class="bg-[#0085CA] hover:bg-[#0085CA]/80 uppercase text-white rounded-sm text-sm px-3">Ändern</button>
            </div>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="far fa-trash text-red-500 mr-1"></i> Alle Einträge löschen</span>
            <span class="text-xs text-gray-500">Hier können alle Einträge des Auftrags in allen Bearbeitungsschritten gelöscht werden. Diese aktion kann nicht rückgängig gemacht werde und ist deshalb mit Vorischt zu geniessen!</span>
            <button class="bg-red-500 hover:bg-red-500/80 uppercase text-white rounded-sm text-sm px-3 py-1.5 w-fit mt-3"><i class="far fa-exclamation-triangle mr-1"></i> Alle Einträge Löschen</button>
        </div>
    </div>
</div>
