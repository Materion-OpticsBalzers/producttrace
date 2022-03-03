<div class="flex flex-col bg-white w-full h-full pt-28 z-[9] border-l border-gray-200">
    <div class="pl-8 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2">{{ $block->avo }}</span>
        {{ $block->name }}
    </div>
    <div class="h-full bg-gray-100 flex z-[7]">
        <div class="lg:max-w-md lg:min-w-md w-full bg-white border-r border-gray-200 px-8 pt-3 ">
            <h1 class="text-base font-bold">Eintrag hinzufügen</h1>
            <div class="flex flex-col gap-2 mt-3" x-data="{ wafer: '' }">
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Wafer ID *:</label>
                    <div class="flex gap-1">
                        <input x-model="wafer" @change="$wire.checkWafer(wafer)" type="text" class="bg-gray-100 w-full rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" autofocus tabindex="1" placeholder="Wafer ID"/>
                        <button @click="$wire.checkWafer(wafer)" class="bg-[#0085CA] rounded-sm px-3 py-1 uppercase text-white text-left"><i class="fal fa-search"></i></button>
                    </div>
                    @error('wafer') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                    @if(session('success')) <span class="mt-1 text-xs font-semibold text-green-600">Wafernummer ist in Ordnung</span> @endif
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Operator *:</label>
                    <input type="text" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" value="{{ auth()->user()->personnel_number }}" tabindex="2" placeholder="Operator"/>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Box ID *:</label>
                    <input type="text" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" tabindex="3" placeholder="Box ID"/>
                </div>
                <button type="submit" class="bg-[#0085CA] rounded-sm px-3 py-1 uppercase text-white text-left" tabindex="4">Eintrag Speichern</button>
            </div>
        </div>
        <div class="w-full px-4 py-3">
            <h1 class="text-base font-bold">Wafers</h1>
        </div>
    </div>
</div>
