<div class="flex flex-col bg-white w-full h-full pt-28 z-[9] border-l border-gray-200">
    <div class="pl-8 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2">{{ $block->avo }}</span>
        {{ $block->name }}
    </div>
    <div class="h-full bg-gray-100 flex z-[7]">
        <div class="lg:max-w-sm lg:min-w-sm w-full bg-white border-r border-gray-200 px-8 pt-3 overflow-y-auto pb-20">
            <h1 class="text-base font-bold flex justify-between items-center">
                Eintrag hinzufügen
                <a href="javascript:;" class="px-3 py-1 hover:bg-gray-50"><i class="far fa-arrow-left"></i></a>
            </h1>
            <div class="flex flex-col gap-2 mt-3" x-data="{ wafer: '', operator: {{ auth()->user()->personnel_number }}, boxId: '', rejection: null }">
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Wafer ID *:</label>
                    <div class="flex gap-1">
                        <input x-model="wafer" @change="$wire.checkWafer(wafer)" type="text" class="bg-gray-100 w-full rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" autofocus tabindex="1" placeholder="Wafer ID"/>
                        <button @click="$wire.checkWafer(wafer)" class="bg-[#0085CA] rounded-sm px-3 py-1 uppercase text-white text-left"><i class="fal fa-search"></i></button>
                    </div>
                    <span class="text-xs text-gray-500 animate-pulse mt-1" wire:loading wire:target="checkWafer">Wafer wird geprüft...</span>
                    @error('wafer') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                    @if(session()->has('waferCheck')) <span class="mt-1 text-xs font-semibold text-green-600">Wafernummer ist in Ordnung</span> @endif
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Operator *:</label>
                    <input x-model="operator" type="text" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" tabindex="2" placeholder="Operator"/>
                    @error('operator') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Box ID *:</label>
                    <input x-model="boxId" type="text" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" tabindex="3" placeholder="Box ID"/>
                    @error('box') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Ausschussgrund *:</label>
                    <fieldset class="flex flex-col gap-0.5">
                        @foreach($rejections as $rejection)
                            <label class="flex px-3 py-1 @if($rejection->reject) bg-red-100/50 @else bg-green-100/50 @endif rounded-sm items-center">
                                <input x-model="rejection" value="{{ $rejection->id }}" type="radio" class="text-[#0085CA] border-gray-300 rounded-sm focus:ring-[#0085CA] mr-2" name="rejection">
                                <span class="text-xs text-gray-500">{{ $rejection->name }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('rejection') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                @error('response') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                @if(session()->has('success')) <span class="mt-1 text-xs font-semibold text-green-600">Eintrag wurde erfolgreich gespeichert</span> @endif
                @if($this->waferError)
                    <button type="submit" disabled class="bg-red-500/80 rounded-sm px-3 py-1 text-sm uppercase text-white text-left" tabindex="4">Wafer nicht gültig...</button>
                @else
                    <button type="submit" @click="$wire.addEntry(wafer, '{{ $orderId }}', {{ $blockId }}, operator, boxId, rejection)" class="bg-[#0085CA] hover:bg-[#0085CA]/80 rounded-sm px-3 py-1 text-sm uppercase text-white text-left" tabindex="4">
                        <span wire:loading.remove wire:target="addEntry">Eintrag Speichern</span>
                        <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                    </button>
                @endif
            </div>
        </div>
        <div class="w-full px-4 py-3 overflow-y-auto">
            <h1 class="text-base font-bold">Wafers</h1>
            <div class="flex flex-col gap-1 mt-2">
                @foreach($wafers as $wafer)
                    <div class="px-2 py-1 bg-white shadow-sm rounded-sm hover:bg-gray-50 flex">
                        <span class="text-sm">{{ $wafer->wafer_id }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
