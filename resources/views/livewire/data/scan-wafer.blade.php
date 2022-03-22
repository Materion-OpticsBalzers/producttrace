<div class="h-full w-full flex flex-col justify-center items-center max-w-md mx-auto" x-data="{ wafer: '' }">
    <h1 class="text-4xl font-bold">Wafer verfolgen</h1>
    <div class="flex w-full items-center mt-2 shadow-md">
        <div class="h-12" wire:loading>
            <div class="bg-white rounded-l-sm h-full flex items-center px-3"><i class="fal fa-sync animate-spin"></i></div>
        </div>
        <input type="text" x-model="wafer" @keyup.enter="$wire.scanWafer(wafer)" class="h-12 font-semibold rounded-sm border-0 focus:ring-[#0085CA] grow" placeholder="Wafer eingeben..." autofocus>
    </div>
    @error('wafer') <span class="text-red-500 font-bold text-sm mt-2">{{ $message }}</span> @enderror
</div>
