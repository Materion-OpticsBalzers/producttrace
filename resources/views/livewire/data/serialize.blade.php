<div class="h-full flex" x-data="{ selected: [], showLists: false }">
    <div class="bg-white flex flex-col max-w-sm min-w-sm w-full pt-32 px-4 gap-2 shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[8]">
        <h1 class="font-semibold text-lg">Filter</h1>
        <input type="text" wire:model.lazy="search" class="rounded-sm border-0 focus:ring-[#0085CA] font-semibold bg-gray-200" placeholder="Artikel suchen..." />
        <input type="text" wire:model.lazy="searchAb" class="rounded-sm border-0 focus:ring-[#0085CA] font-semibold bg-gray-200" placeholder="AB suchen..." />
        <label class="flex items-center text-xs text-gray-500">
            <input type="checkbox" wire:model="showSet" class="mx-1 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" />
            Zugewiesene anzeigen
        </label>
    </div>
    <div class="flex flex-col pt-28 w-full">
        <div class="flex shadow-md w-full divide-x divide-gray-200 z-[7]">
            <div @click="showLists = false" class="w-full bg-white p-4 flex flex-col rounded-sm hover:bg-gray-50 cursor-pointer" :class="!showLists ? 'text-[#0085CA]' : ''">
                <span class="uppercase font-semibold" ><i class="fal fa-memo-pad mr-1"></i> Produktionsaufträge</span>
                <span class="text-xs text-gray-400">Zeigt Produktionsaufträge an die zugewiesen werden können</span>
            </div>
            <div @click="showLists = true" class="w-full bg-white p-4 flex flex-col rounded-sm hover:bg-gray-50 cursor-pointer" :class="showLists ? 'text-[#0085CA]' : ''">
                <span class="uppercase font-semibold" ><i class="fal fa-list mr-1"></i>  Serialisationslisten</span>
                <span class="text-xs text-gray-400">Zeigt bereits erstellte Serialisationslisten an</span>
            </div>
        </div>
        <div class="flex w-full">
            <div class="flex flex-col gap-2 w-full h-full overflow-y-auto" x-show="!showLists">
                <div class="bg-white flex flex-col divide-y divide-gray-200" wire:loading.remove.delay>
                    @forelse($orders as $order)
                        @if(isset($order->po))
                            <label class="items-center px-4 py-2 gap-2 hover:bg-gray-50 grid grid-cols-5">
                                <span class="font-semibold items-center gap-2 flex">
                                    <a href="javascript:;" wire:click="unlink({{ $order->id }})" class="text-red-500 fa-fw"><i class="fal fa-unlink"></i></a>
                                    {{ $order->id }}
                                </span>
                                <span>Art: {{ $order->article }}</span>
                                <span>{{ $order->article_cust }}</span>
                                <span>Po: {{ $order->po }} - {{ $order->po_pos }}</span>
                                <span class="text-gray-600">{{ $order->serials->first()->id ?? '' }} - {{ $order->serials->last()->id ?? '' }} ({{ $order->serials->count() }})</span>
                            </label>
                        @else
                            <label class="items-center gap-2 p-2 hover:bg-gray-50 grid grid-cols-5">
                                <span class="font-semibold gap-2 flex items-center">
                                    <input type="checkbox" value="{{ $order->id }}" class="mx-1 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" x-model="selected" />
                                    {{ $order->id }}
                                </span>
                                <span class="col-span-2">Art: {{ $order->article }}</span>
                                <span>{{ $order->article_cust }}</span>
                                <span class="text-gray-600">{{ $order->serials->first()->id ?? '' }} - {{ $order->serials->last()->id ?? '' }} ({{ $order->serials->count() }})</span>
                            </label>
                        @endif
                    @empty
                        <div class="text-center py-5">
                            <h1 class="font-bold text-lg text-red-500">Keine Aufträge gefunden!</h1>
                            <span class="text-sm text-gray-500">Es wurden keine Aufträge gefunden die noch nicht zugewiesen sind, um zugewiesene Aufträge zu sehen wähle beim Filter "Zugewiesene anziegen" an.</span>
                        </div>
                    @endforelse
                </div>
                <div class="text-center bg-white py-5" wire:loading.delay>
                    <h1 class="font-bold text-lg"><i class="fal fa-spinner animate-spin mr-1"></i> Aufträge werden geladen...</h1>
                </div>
            </div>
            <div class="right-0 absolute fixed h-max max-w-xs min-w-xs w-full bg-white shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[6]" x-data="{ po: '', pos: '' }" x-show="selected.length > 0" x-transition>
                <div class="flex flex-col gap-2 p-2">
                    <input type="text" x-model="po" class="rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-gray-200 shadow-sm" placeholder="Auftragsbestätigung" />
                    <input type="text" x-model="pos" class="rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-gray-200 shadow-sm" placeholder="Start Pos z.B (10)" />
                    <button @click="$wire.setOrder(selected, po, pos)" class="bg-[#0085CA] font-semibold text-sm h-8 text-white hover:bg-[#0085CA]/80 h-full px-2 rounded-sm">Ausgewählte Aufträge zuweisen</button>
                    @if(session()->has('success')) <span class="text-xs text-green-600 mt-1">Erfolgreich zugewiesen</span> @endif
                    @error('po') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                    @error('pos') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex flex-col w-full gap-1 overflow-y-auto" x-show="showLists">
                <div class="bg-white flex flex-col divide-y divide-gray-200">
                    @forelse($serialLists as $list)
                        <a href="{{ route('serialise.list', ['po' => $list->id]) }}" class="items-center gap-2 px-4 py-2 hover:bg-gray-50 grid grid-cols-5">
                            <span class="font-semibold">
                                {{ $list->id }}
                            </span>
                            <span>Art: {{ $list->article }}</span>
                            <span>{{ $list->article_cust }}</span>
                            <span>{{ $list->format }}</span>
                        </a>
                    @empty
                        <div class="text-center py-10">
                            <h1 class="font-bold text-lg text-red-500">Keine Listen gefunden!</h1>
                            <span class="text-sm text-gray-500">Es wurden noch keine Serialisationslisten erstellt oder mit dem Filter wurde nichts gefunden.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
