<div class="h-full w-full flex flex-col justify-center items-center max-w-md mx-auto" x-data="{ order: '' }">
    <h1 class="text-4xl font-bold">Auftrag scannen</h1>
    <input type="text" x-model="order" @keyup.enter="$wire.scanOrder(order)" class="h-12 mt-2 font-semibold rounded-sm border-0 shadow-md focus:ring-[#0085CA] w-full" placeholder="Auftrag scannen oder eingeben..." autofocus>
    @error('order') <span class="text-red-500 font-bold text-sm mt-2">{{ $message }}</span> @enderror
</div>
