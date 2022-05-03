<div class="max-w-6xl min-w-6xl mx-auto h-full flex flex-col mx-auto pt-32" x-data="{ selected: [], showLists: false }">
    <h1 class="font-bold text-xl">Serialisierung</h1>
    <span class="text-sm text-gray-500">Wähle den auftrag aus den du Serialisieren möchtest</span>
    <input type="text" wire:model.lazy="search" class="mt-2 rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-white shadow-sm" placeholder="Artikel suchen..." />
    <input type="text" wire:model.lazy="searchAb" class="mt-2 rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-white shadow-sm" placeholder="AB suchen..." />
    <label class="flex items-center text-xs mt-1">
        <input type="checkbox" wire:model="showSet" class="mx-1 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" />
        Zugewiesene anzeigen
    </label>
    <div class="flex gap-2 mt-3">
        <div @click="showLists = false" class="bg-white p-4 rounded-sm hover:bg-gray-50 cursor-pointer" :class="!showLists ? 'text-[#0085CA]' : ''">
            <span class="uppercase font-semibold" >Aufträge</span>
        </div>
        <div @click="showLists = true" class="bg-white p-4 rounded-sm hover:bg-gray-50 cursor-pointer" :class="showLists ? 'text-[#0085CA]' : ''">
            <span class="uppercase font-semibold" >Serialisationslisten</span>
        </div>
    </div>
    <div class="flex flex-col gap-2" x-show="!showLists" x-transition>
        <div class="flex gap-2 items-center mt-2" x-data="{ po: '', pos: '' }">
            <input type="text" x-model="po" class="rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-white shadow-sm" placeholder="Auftragsbestätigung" />
            <input type="text" x-model="pos" class="rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-white shadow-sm" placeholder="Start Pos z.B (10)" />
            <button @click="$wire.setOrder(selected, po, pos)" class="bg-[#0085CA] font-semibold text-sm text-white hover:bg-[#0085CA]/80 h-full px-2 rounded-sm">Setzen</button>
        </div>
        @if(session()->has('success')) <span class="text-xs text-green-600 mt-2">Erfolgreich zugewiesen</span> @endif
        @error('po') <span class="text-xs text-red-500 mt-2">{{ $message }}</span> @enderror
        @error('pos') <span class="text-xs text-red-500 mt-2">{{ $message }}</span> @enderror
        <div class="bg-white p-2 flex flex-col">
            @forelse($orders as $order)
                @if(isset($order->po))
                    <label class="items-center gap-2 hover:bg-gray-100 grid grid-cols-5">
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
                    <label class="items-center gap-2 hover:bg-gray-100 grid grid-cols-5">
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
            @endforelse
        </div>
    </div>
    <div class="mt-2 flex flex-col gap-1 overflow-y-auto" x-show="showLists" x-transition>
        <div class="bg-white p-2 flex flex-col">
            @forelse($serialLists as $list)
                <a href="{{ route('serialise.list', ['po' => $list->id]) }}" class="items-center gap-2 hover:bg-gray-100 grid grid-cols-5">
                    <span class="font-semibold">
                        {{ $list->id }}
                    </span>
                    <span>Art: {{ $list->article }}</span>
                    <span>{{ $list->article_cust }}</span>
                    <span>{{ $list->format }}</span>
                </a>
            @empty
            @endforelse
        </div>
    </div>
</div>
