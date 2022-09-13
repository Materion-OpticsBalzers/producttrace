<x-app-layout>
    <div class="flex h-full overflow-hidden">
        <livewire:data.order-panel :orderId="$order"/>
        <div class="flex flex-col w-full h-full pt-28 z-[9] border-l border-gray-200 justify-center items-center">
            <h1 class="font-extrabold text-2xl"><i class="far fa-sync animate-spin mr-1"></i> Arbeitsschritt auswählen...</h1>
            <span class="font-normal text-gray-500 text-base">Bitte wähle einen Arbeitschritt auf der linken Seite aus!</span>
        </div>
    </div>
</x-app-layout>
