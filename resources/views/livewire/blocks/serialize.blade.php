<div class="flex flex-col bg-gray-100 w-full h-full z-[9] border-l border-gray-200 overflow-y-auto">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8] bg-white sticky top-0">
        <i class="far fa-link mr-2"></i>
        <span class="grow">{{ $block->name }}</span>
        @if($serials->count() > 0)
            <a href="javascript:;" onclick="confirm('Bist du sicher das du alle Einträge löschen willst?') || event.stopImmediatePropagation()" wire:click="clear({{ $orderId }}, {{ $blockId }})" class="bg-red-500 hover:bg-red-500/80 rounded-sm px-2 py-1 text-sm text-white uppercase font-semibold mt-1">Alle Positionen Löschen</a>
        @endif
    </div>
    <div class="px-8 py-3 bg-white border-b border-gray-200 shadow-sm z-[7] flex flex-col">
        <div class="flex gap-4 items-center" x-data="">
            <i class="far fa-sync animate-spin" wire:loading wire:target="importSerials"></i>
            @if($serials->count() == 0)
                <a href="javascript:;" @click="$wire.importSerials()" wire:loading.remove wire:target="importSerials" class="bg-[#0085CA] px-2 py-1 text-sm text-white hover:bg-[#0085CA]/80 rounded-sm uppercase"><i class="fal fa-upload mr-1"></i> Importieren</a>
            @endif
            <span class="text-xs text-gray-500"><i class="far text-[#0085CA] fa-exclamation-triangle mr-0.5"></i> Die Wafer werden automatisch gesucht, falls der Import nicht funktioniert konnte das Log-File für diesen Auftrag nicht gefunden werden!
            Wenn ein Wafer importiert wird der schon vorhanden ist wird dieser ignoriert.</span>
        </div>
        @if(session()->has('success')) <span class="text-xs mt-2 font-semibold text-green-600">Wafer wurden erfolgreich importiert!</span> @endif
        @error('import') <span class="text-xs mt-2 font-semibold text-red-500">{{ $message }}</span> @endif
    </div>
    <div class="h-full bg-gray-100 flex z-[7]">
        <div class="w-full px-4 py-3 flex flex-col pb-4">
            <h1 class="text-base font-bold">Importierte Wafer ({{ $serials->count() }})</h1>
            <input type="text" wire:model.lazy="search" onfocus="this.setSelectionRange(0, this.value.length)" class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]" placeholder="Wafer durchsuchen..." />
            <div class="flex flex-col gap-1 mt-2" wire:loading.remove.delay.longer wire:target="search">
                <div class="px-2 py-1 rounded-sm grid grid-cols-3 items-center justify-between bg-gray-200 shadow-sm mb-1">
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                    <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                    <span class="text-sm font-bold text-right"><i class="fal fa-tools mr-1"></i> Aktion</span>
                </div>
                @forelse($serials as $serial)
                    <div class="px-2 py-1 bg-white border @if($serial->rejected) border-red-500/50 @else border-green-600/50 @endif  flex rounded-sm hover:bg-gray-50 items-center">
                        <div class="flex items-center w-full">
                            <div class="flex flex-col grow">
                                <div class="flex grid grid-cols-3 items-center">
                                    <span class="text-sm font-semibold">{{ $serial->id }}</span>
                                    <span class="text-xs text-gray-500 truncate">{{ date('d.m.Y H:i', strtotime($serial->created_at)) }}</span>
                                </div>
                                <span class="text-xs text-gray-500">Wafer: {{ $serial->wafer_id }}</span>
                            </div>
                            <div class="text-xs text-gray-500 text-right">
                                @if($serial->rejected)
                                    <a href="javascript:" wire:click="toggleRejected({{ $serial->id }})" class="bg-green-600 text-white px-2 py-2 rounded-md font-semibold text-xs">Kein Ausschuss</a>
                                @else
                                    <a href="javascript:" wire:click="toggleRejected({{ $serial->id }})" class="bg-red-500 text-white px-2 py-2 rounded-md font-semibold text-xs">Ausschuss setzen</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col justify-center items-center p-10">
                        <span class="text-lg font-bold text-red-500">Keine Wafer gefunden!</span>
                        <span class="text-sm text-gray-500">Es wurden keine Wafer in diesem Arbeitsschritt gefunden.</span>
                    </div>
                @endforelse
            </div>
            <div class="flex flex-col justify-center items-center p-10 animate-pulse text-center" wire:loading.delay.longer wire:target="search">
                <span class="text-lg font-bold text-red-500">Wafer werden geladen...</span><br>
                <span class="text-sm text-gray-500">Die Wafer werden geladen, wenn dieser Vorgang zu lange dauert bitte die Seite neu laden.</span>
            </div>
        </div>
    </div>
</div>
