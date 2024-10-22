<div class="h-full w-full flex flex-col justify-center items-center max-w-md mx-auto relative">
    <h1 class="text-4xl font-bold">Wafer verfolgen</h1>
    <select wire:model="searchType" class="font-semibold rounded-sm border-0 mt-2 w-full shadow-md focus:ring-[#0085CA]">
        <option value="wafer">Wafer ID</option>
        <option value="serial">Serial</option>
        <option value="box">Box</option>
        <option value="chrome">Chromcharge</option>
        <option value="ar">AR Charge</option>
    </select>
    <div class="flex w-full items-center mt-2 shadow-md">
        <div class="h-12" wire:loading>
            <div class="bg-white rounded-l-sm h-full flex items-center px-3"><i class="fal fa-sync animate-spin"></i></div>
        </div>
        <input type="text" wire:model="search" class="h-12 font-semibold rounded-sm border-0 focus:ring-[#0085CA] grow" placeholder="Wafer eingeben..." autofocus>
        <a href="javascript:;" wire:click="$refresh" class="px-2 hover:bg-gray-200 h-full flex items-center font-semibold">Suchen</a>
    </div>
    @error('wafer') <span class="text-red-500 font-bold text-sm mt-2">{{ $message }}</span> @enderror
    @if(sizeof($wafers) > 0)
        <div class="flex flex-col w-full bg-white shadow-md divide-y divide-gray-200 mt-2 overflow-y-auto max-h-40">
            <div class="text-xs px-2 py-0.5 text-gray-500">{{ $wafers->count() }} Gefundene Wafer</div>
            @foreach($wafers as $wafer)
                <a href="{{ route('wafer.show', ['wafer' => $wafer->wafer_id]) }}" target="_blank" class="flex hover:bg-gray-50 flex-col w-full px-2 py-2">
                    <span class="text-sm font-semibold">{{ $wafer->wafer_id }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
