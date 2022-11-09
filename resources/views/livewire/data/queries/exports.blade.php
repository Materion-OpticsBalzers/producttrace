<div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 pb-4 w-full">

    <h1 class="text-xl font-bold mb-2">Verfügbare exports</h1>
    <div class="grid grid-cols-2 mt-4 gap-2">
        <div class="flex flex-col bg-white rounded-md shadow-md relative">
            <div class="absolute w-full h-full rounded-md bg-white bg-opacity-50 flex justify-center items-center" wire:loading></div>
            <span class="flex items-center font-semibold px-2 py-1 bg-gray-200 rounded-t-md">
                <i class="fal fa-file-export mr-1"></i> Alle Daten per Zeitraum pro Wafer ID
            </span>
            <div class="grid grid-cols-2 gap-2 py-2 px-2">
                <div class="flex rounded-md bg-gray-100">
                    <span class="bg-gray-200 flex items-center text-xs font-semibold px-2 rounded-l-md">Von:</span>
                    <input type="date" class="w-full rounded-md bg-gray-100 focus:ring-0 font-semibold text-sm border-none" wire:model.defer="exportAllWafersFrom" />
                </div>
                <div class="flex rounded-md bg-gray-100">
                    <span class="bg-gray-200 flex items-center text-xs font-semibold px-2 rounded-l-md">Bis:</span>
                    <input type="date" class="w-full rounded-md bg-gray-100 font-semibold focus:ring-0 text-sm border-none" wire:model.defer="exportAllWafersTo" />
                </div>
            </div>
            @error('exportAllWafers')
                <span class="text-xs font-semibold bg-red-100 px-2 py-0.5 text-red-500">{{ $message }}</span>
            @enderror
            @if(session('exportAllWafers'))
                <span class="text-xs font-semibold bg-green-100 px-2 py-0.5 text-green-500">Erfolgreich exportiert</span>
            @endif
            <a href="javascript:" wire:click="exportAllWafers" class="rounded-b-md bg-[#0085CA] font-semibold text-sm text-white px-2 py-1">Exportieren</a>
        </div>
        <a href="javascript:" class="flex bg-white px-2 py-1 font-semibold hover:bg-gray-50 items-center"><i class="fal fa-file-export mr-1"></i> Alle Daten pro Serial ID</a>
    </div>
</div>
