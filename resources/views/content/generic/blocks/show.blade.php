<x-app-layout>
    <livewire:data.fastpanel />
    <div class="flex h-full">
        <livewire:data.order-panel :order-id="$order" />

        @if(\Illuminate\Support\Facades\View::exists('livewire.blocks.'. $block->identifier))
            @livewire('blocks.'. $block->identifier, ['blockId' => $block->id])
        @else
            <div class="flex flex-col w-full h-full pt-28 z-[9] border-l border-gray-200 justify-center items-center">
                <h1 class="font-extrabold text-2xl text-red-500"><i class="far fa-times mr-1"></i> Modul nicht gefunden!</h1>
                <span class="font-normal text-gray-500 text-base text-center">Der Arbeitsvorgang wurde nicht gefunden und konnte deshalb nicht geladen werden!<br>
                    Wenn du denkst das dies ein fehler ist dann kontaktiere den Support.</span>
            </div>
        @endif
    </div>
</x-app-layout>
