<x-app-layout>
    <div class="flex h-full w-full overflow-hidden">
        <livewire:data.order-panel :order-id="$order" :block-id="$block->id" />
        @if(\Illuminate\Support\Facades\View::exists('livewire.blocks.'. $block->identifier))
            @livewire('blocks.'. $block->identifier, ['blockId' => $block->id, 'orderId' => $order, 'prevBlock' => $block->info->prev, 'nextBlock' => $block->info->next])
        @else
            <div class="flex flex-col w-full h-full z-[9] border-l border-gray-200 justify-center items-center">
                <h1 class="font-extrabold text-2xl text-red-500"><i class="far fa-times mr-1"></i> Modul nicht gefunden!</h1>
                <span class="font-normal text-gray-500 text-base text-center">Der Arbeitsvorgang wurde nicht gefunden und konnte deshalb nicht geladen werden!<br>
                    Wenn du denkst das dies ein fehler ist dann kontaktiere den Support.</span>
            </div>
        @endif
    </div>
</x-app-layout>
