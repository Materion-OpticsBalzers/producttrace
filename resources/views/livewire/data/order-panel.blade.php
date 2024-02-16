<?php
    use App\Models\Generic\Block;

    new class extends \Livewire\Volt\Component {
        public $order;
        public $block;

        public function with() {
            $blocks = array();
            foreach($this->order->mapping->blocks as $block) {
                if(isset($block->type)) {
                    $blocks[] = (object) $block;
                } else {
                    $b = Block::find($block->id);
                    $b->prev = $block->prev;
                    $b->next = $block->next;
                    $blocks[] = $b;
                }
            }

            return compact(['blocks']);
        }
    }
?>

<div class="bg-white w-20 lg:w-full h-full max-w-xs lg:min-w-xs z-[10] overflow-y-auto" x-data="{ openInfo: false }">
    <div class="px-4 py-3 text-xl font-bold hidden lg:flex bg-white justify-between items-center border-b border-gray-200">
        <div class="flex grow flex-col">
            {{ $order->id }}
            <span class="font-normal text-sm">{{ $order->mapping->product->name }}</span>
            <span class="font-normal text-xs text-gray-600">Artikel: {{ $order->article }} | Kunde: {{ $order->customer }}</span>
            @if($order->supplier)
                <span class="font-normal text-xs text-gray-600">Lieferant: {{ $order->supplier }}</span>
            @endif
            @if($order->po != '')
                <a class="font-normal text-xs text-[#0085CA]" href="{{ route('serialise.list', ['po' => $order->po]) }}" wire:navigate>In Serialisierungsliste anzeigen <i class="fal fa-link"></i></a>
            @endif
        </div>
        <a href="javascript:;" @click="openInfo = !openInfo" class="p-2 rounded-sm hover:bg-gray-100"><i class="far fa-clock-rotate-left text-[#0085CA]"></i></a>
    </div>
    <div class="flex flex-col divide-y divide-gray-200" x-show="!openInfo">
        @forelse($blocks as $b)
            @if(isset($b->type))
                <div class="px-4 py-1.5 font-extrabold bg-gray-100">
                    <i class="{{ $b->icon }} fa-fw mr-1"></i> <span class="hidden lg:block">{{ $b->value }}</span>
                </div>
            @else
                <a href="{{ route('blocks.show', ['order' => $order->id, 'block' => $b->id]) }}" wire:navigate class="flex pl-4 items-center justify-between py-2 @if($b->id == $block->id) bg-gray-50 @endif hover:bg-gray-50">
                    @if($b->icon != '')
                        <span class="text-lg font-bold mr-3 @if($b->id == $block->id) text-[#0085CA] @endif"><i class="far fa-fw {{ $b->icon }}"></i></span>
                    @else
                        <span class="text-lg font-bold mr-3 @if($b->id == $block->id) text-[#0085CA] @endif">{{ sprintf('%02d', $b->avo) }}</span>
                    @endif
                    <div class="hidden lg:flex flex-col grow">
                        <span class="text-base font-semibold @if($b->id == $block->id) text-[#0085CA] @endif">{{ $b->name }} @if($b->admin_only) <i class="far fa-lock ml-0.5"></i> @endif</span>
                        <span class="text-xs text-gray-500">{{ $b->description ?? 'Keine Beschreibung...' }}</span>
                    </div>
                    @if($b->id == $block->id)
                        <i class="far fa-chevron-left animate-pulse pr-4"></i>
                    @endif
                </a>
            @endif
        @empty
            <div class="flex pl-8 items-center py-2">
                <div class="flex flex-col">
                    <span class="text-base font-semibold text-red-500">Keine Blöcke für diesen Auftrag definiert</span>
                    <span class="text-xs text-gray-500">Für diesen auftrag würden noch keine arbeitsschritte definiert.</span>
                </div>
            </div>
        @endforelse
    </div>
</div>
