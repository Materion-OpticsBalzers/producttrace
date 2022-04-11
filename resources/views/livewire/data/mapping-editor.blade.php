<div class="h-full max-w-6xl min-w-6xl mx-auto pt-32 overflow-hidden">
    <h1 class="font-bold text-xl">{{ $mapping->product->name }}</h1>
    <div class="grid grid-cols-3 gap-4 mt-2 h-full">
        <div class="flex flex-col bg-white shadow-sm rounded-sm divide-y divide-gray-200">
            @foreach($blocks as $block)
                @if(isset($block->type))
                    <div class="pl-4 py-1.5 font-extrabold bg-white flex justify-between items-center">
                        <div class="flex flex-col">
                            <span><i class="{{ $block->icon }} fa-fw mr-1"></i> {{ $block->value }}</span>
                            <span class="text-xs text-gray-600">Trennblock</span>
                        </div>
                        <a href="javascript:;" class="text-red-500"><i class="far fa-trash pr-4"></i></a>
                    </div>
                @else
                    <div class="flex pl-4 items-center justify-between py-2 hover:bg-gray-50">
                        @if($block->icon != '')
                            <span class="text-lg font-bold mr-3"><i class="far fa-fw {{ $block->icon }}"></i></span>
                        @else
                            <span class="text-lg font-bold mr-3">{{ sprintf('%02d', $block->avo) }}</span>
                        @endif
                        <div class="flex flex-col grow">
                            <span class="text-base font-semibold">{{ $block->name }} @if($block->admin_only) <i class="far fa-lock ml-0.5"></i> @endif</span>
                            <span class="text-xs text-gray-500">{{ $block->description ?? 'Keine Beschreibung...' }}</span>
                        </div>
                        <a href="javascript:;" class="text-red-500"><i class="far fa-trash pr-4"></i></a>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="flex flex-col col-span-2 bg-white rounded-sm divide-y divide-gray-200 overflow-y-auto pb-10">
            <div class="flex flex-col px-4 py-1.5 sticky top-0 w-full bg-white border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <input type="text" wire:model.defer="articleAdd" class="bg-gray-200 rounded-sm font-semibold text-xs rounded-sm border-0 focus:ring-[#0085CA] w-full" placeholder="Artikel hinzufügen">
                    <button class="bg-[#0085CA] text-white font-semibold hover:bg-[#0085CA]/80 px-2 rounded-sm text-sm h-full" wire:click="addArticle">Hinzufügen</button>
                </div>
                @error('article') <span class="text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
            </div>
            @foreach($articles as $article)
                <div class="pl-4 py-1.5 font-extrabold flex justify-between">
                    <span>{{ $article }}</span>
                    <a href="javascript:;" wire:click="removeArticle({{ $article }})" class="text-red-500"><i class="far fa-trash pr-4"></i></a>
                </div>
            @endforeach
        </div>
    </div>
</div>
