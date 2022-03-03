<div class="h-full w-full flex flex-col justify-center items-center max-w-md mx-auto" x-data="{ order: '' }">
    <h1 class="text-4xl font-bold">Auftrag scannen</h1>
    <div class="flex w-full items-center mt-2 shadow-md">
        <div class="h-12" wire:loading>
            <div class="bg-white rounded-l-sm h-full flex items-center px-3"><i class="fal fa-sync animate-spin"></i></div>
        </div>
        <input type="text" x-model="order" @keyup.enter="$wire.scanOrder(order)" class="h-12 font-semibold rounded-sm border-0 focus:ring-[#0085CA] grow" placeholder="Auftrag scannen oder eingeben..." autofocus>
    </div>
    @error('order') <span class="text-red-500 font-bold text-sm mt-2">{{ $message }}</span> @enderror
</div>
