<div class="flex flex-col bg-white w-full h-full pt-28 z-[9] border-l border-gray-200">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2">{{ $block->avo }}</span>
        <span class="grow">{{ $block->name }}</span>
        @if($wafers->count() > 0)
            <a href="javascript:;" wire:click="clear({{ $orderId }}, {{ $blockId }})" class="hover:bg-gray-50 rounded-sm px-2 py-1 text-sm text-red-500 font-semibold mt-1"><i class="far fa-trash mr-1"></i> Alle Positionen Löschen</a>
        @endif
    </div>
    <div class="h-full bg-gray-100 flex z-[7]" x-data="{ hidePanel: $persist(false) }" :class="hidePanel ? '' : 'flex-col'">
        <a href="javascript:;" @click="hidePanel = false" class="h-full bg-white w-12 p-3 border-r border-gray-200 hover:bg-gray-50" x-show="hidePanel">
            <p class="transform rotate-90 font-bold w-full text-lg whitespace-nowrap"><i class="far fa-chevron-up mr-3"></i> Eintrag hinzufügen</p>
        </a>
        <div class="w-full h-full bg-white border-r border-gray-200 px-8 pt-3 overflow-y-auto pb-20" x-show="!hidePanel">
            <h1 class="text-base font-bold flex justify-between items-center">
                Eintrag hinzufügen
                <a href="javascript:;" @click="hidePanel = true" class="px-3 py-1 text-sm rounded-sm font-semibold hover:bg-gray-50"><i class="far fa-eye mr-1"></i> Einträge anzeigen ({{ $wafers->count() }})</a>
            </h1>
            <div class="flex flex-col gap-2 mt-3" x-data="{ wafer: '', operator: {{ auth()->user()->personnel_number }}, rejection: 6 }">
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Wafer ID *:</label>
                    <div class="flex flex-col w-full relative" x-data="{ show: false, search: '' }" @click.away="show = false">
                        <div class="flex flex-col">
                            <div class="flex">
                                <div class="bg-gray-100 rounded-l-sm flex items-center px-2">
                                    <i class="far fa-sync animate-spin"></i>
                                </div>
                                <input type="text" wire:model.lazy="selectedWafer" id="wafer" tabindex="1" onfocus="this.setSelectionRange(0, this.value.length)" @focus="show = true" @focusout="show = false" class="w-full bg-gray-100 rounded-sm font-semibold text-sm border-0 focus:ring-[#0085CA]" placeholder="Wafer ID eingeben oder scannen..."/>
                            </div>
                            @if(session()->has('waferScanned')) <span class="text-xs mt-1 text-green-600">Gescannter Wafer geladen!</span> @endif
                        </div>
                        <div class="shadow-lg rounded-sm absolute w-full mt-10 border border-gray-300 bg-gray-200 overflow-y-auto max-h-60" x-show="show" x-transition>
                            <div class="flex flex-col divide-y divide-gray-300" wire:loading.remove>
                                <div class="px-2 py-1 text-xs text-gray-500">{{ sizeof($sWafers) }} Wafer @if($prevBlock != null) von vorherigem Schritt @endif</div>
                                @forelse($sWafers as $wafer)
                                    <a href="javascript:;" wire:click="updateWafer('{{ $wafer->wafer_id }}', '{{ $wafer->box }}')" class="flex items-center px-2 py-1 text-sm hover:bg-gray-100">
                                        @if($wafer->wafer->rejected)
                                            <i class="far fa-ban text-red-500 mr-2"></i>
                                        @else
                                            <i class="far fa-check text-green-600 mr-2"></i>
                                        @endif
                                        <div class="flex flex-col">
                                            {{ $wafer->wafer_id }}
                                            <span class="text-xs text-gray-500"><i class="fal fa-box-open"></i> Box: {{ $wafer->box }}</span>
                                            @if($wafer->wafer->rejected)
                                                <span class="text-xs text-red-500 italic"><b>{{ $wafer->wafer->rejection_reason }}</b> in {{ $wafer->wafer->rejection_order }} <i class="fal fa-arrow-right"></i> {{ $wafer->wafer->rejection_avo }} - {{ $wafer->wafer->rejection_position }} </span>
                                            @else
                                                <span class="text-xs text-green-600 italic">Wafer ist in Ordnung</span>
                                            @endif
                                        </div>
                                    </a>
                                @empty
                                    @if($this->search != '')
                                        <div class="px-2 py-1 text-sm">Keine Wafer gefunden!</div>
                                    @else
                                        <div class="px-2 py-1 text-sm">Scanne oder tippe eine Wafer ID ein...</div>
                                    @endif
                                @endforelse
                            </div>
                            <div class="flex w-full px-2 py-1 text-sm" wire:loading><i class="fal fa-refresh animate-spin mr-1"></i> Wafer werden geladen</div>
                        </div>
                    </div>
                    @error('wafer') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                    @if(session()->has('waferCheck')) <span class="mt-1 text-xs font-semibold text-green-600">Wafernummer ist in Ordnung</span> @endif
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Operator *:</label>
                    <input x-model="operator" onfocus="this.setSelectionRange(0, this.value.length)" type="text" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" tabindex="2" placeholder="Operator"/>
                    @error('operator') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm text-gray-500">Box ID *:</label>
                    <input wire:model.defer="box" onfocus="this.setSelectionRange(0, this.value.length)" type="text" class="mt-1 bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold" tabindex="3" placeholder="Box ID"/>
                    @error('box') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm text-gray-500">Anlagennummer *:</label>
                    <span class="text-xs font-light italic">Anlage wird automatisch gezogen, kann jedoch geändert werden</span>
                    <select wire:model.defer="machine" class="mt-1 bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold">
                        <option value="" disabled>Nicht gefunden</option>
                        <option value="Litho 1">Litho 1</option>
                        <option value="Litho 2">Litho 2</option>
                    </select>
                    @error('machine') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Ausschussgrund *:</label>
                    <fieldset class="flex flex-col gap-0.5">
                        @forelse($rejections as $rejection)
                            <label class="flex px-3 py-3 @if($rejection->reject) bg-red-100/50 @else bg-green-100/50 @endif rounded-sm items-center">
                                <input x-model="rejection" value="{{ $rejection->id }}" type="radio" class="text-[#0085CA] border-gray-300 rounded-sm focus:ring-[#0085CA] mr-2" name="rejection">
                                <span class="text-sm">{{ $rejection->name }}</span>
                            </label>
                        @empty
                            <span class="text-xs text-red-500 font-semibold">Ausschussgründe wurden noch nicht definiert...</span>
                        @endforelse
                    </fieldset>
                    @error('rejection') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                </div>
                @error('response') <span class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</span> @enderror
                @if(session()->has('success')) <span class="mt-1 text-xs font-semibold text-green-600">Eintrag wurde erfolgreich gespeichert</span> @endif
                <button type="submit" @click="$wire.addEntry('{{ $orderId }}', {{ $blockId }}, operator, rejection)" class="bg-[#0085CA] hover:bg-[#0085CA]/80 rounded-sm px-3 py-1 text-sm uppercase text-white text-left" tabindex="4">
                    <span wire:loading.remove wire:target="addEntry">Eintrag Speichern</span>
                    <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                </button>
            </div>
        </div>
        <div class="w-full px-4 py-3 overflow-y-auto flex flex-col pb-20" x-show="hidePanel">
            <h1 class="text-base font-bold">Eingetragene Wafer ({{ $wafers->count() }})</h1>
            <input type="text" wire:model.lazy="search" onfocus="this.setSelectionRange(0, this.value.length)" class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]" placeholder="Wafer durchsuchen..." />
            <div class="flex flex-col gap-1 mt-2" wire:loading.remove.delay.longer wire:target="search">
                <div class="px-2 py-1 rounded-sm grid grid-cols-6 items-center justify-between bg-gray-200 shadow-sm mb-1">
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                    <span class="text-sm font-bold"><i class="fal fa-user mr-1"></i> Operator</span>
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Box ID</span>
                    <span class="text-sm font-bold"><i class="fal fa-robot mr-1"></i> Anlage</span>
                    <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                    <span class="text-sm font-bold text-right"><i class="fal fa-cog mr-1"></i> Aktionen</span>
                </div>
                @forelse($wafers as $wafer)
                    <div class="bg-white border @if($wafer->rejection->reject) border-red-500/50 @else border-green-600/50 @endif flex flex-col rounded-sm hover:bg-gray-50 items-center" x-data="{ waferOpen: false, waferEdit: false }">
                        <div class="flex flex-col px-2 py-2 w-full" x-show="waferEdit" x-trap="waferEdit" x-data="{ operator: '{{ $wafer->operator }}', box: '{{ $wafer->box }}', lot: '{{ $wafer->lot }}', machine: '{{ $wafer->machine }}', rejection: {{ $wafer->rejection_id }} }">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-gray-500">Wafer (Nicht änderbar)</label>
                                <input disabled type="text" value="{{ $wafer->wafer_id }}" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"/>
                                <label class="text-xs text-gray-500">Operator</label>
                                <input x-model="operator" type="text" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold" placeholder="Operator"/>
                                <label class="text-xs text-gray-500">Box</label>
                                <input x-model="box" type="text" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold" placeholder="Box ID"/>
                                <label class="text-xs text-gray-500">Anlage</label>
                                <select x-model="machine" class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold">
                                    <option value="Litho 1">Litho 1</option>
                                    <option value="Litho 2">Litho 2</option>
                                </select>
                            </div>
                            <label class="text-xs text-gray-500 mt-1">Ausschussgrund</label>
                            <select x-model="rejection" class="bg-gray-200 rounded-sm border-0 mt-1 focus:ring-[#0085CA] text-xs font-semibold">
                                @forelse($rejections->lazy() as $rejection)
                                    <option value="{{ $rejection->id }}">{{ $rejection->name }}</option>
                                @empty
                                    <option value="" disabled>Keine Ausschussgründe definiert</option>
                                @endforelse
                            </select>
                            <div class="flex gap-1 mt-2">
                                <a href="javascript:;" @click="$wire.updateEntry({{ $wafer->id }}, operator, box, rejection); waferEdit = false" class="bg-[#0085CA] hover:bg-[#0085CA]/80 text-white rounded-sm px-2 py-1 uppercase text-xs">Speichern</a>
                                <a href="javascript:;" @click="waferEdit = false" class="bg-red-500 hover:bg-red-500/80 text-white rounded-sm px-2 py-1 uppercase text-xs">Abbrechen</a>
                            </div>
                        </div>
                        <div class="flex w-full px-2 py-1 cursor-pointer items-center" @click="waferOpen = !waferOpen" x-show="!waferEdit">
                            <i class="fal fa-chevron-down mr-2" x-show="!waferOpen"></i>
                            <i class="fal fa-chevron-up mr-2" x-show="waferOpen"></i>
                            <div class="flex flex-col grow">
                                <div class="grid grid-cols-6 items-center">
                                    <span class="text-sm font-semibold">{{ $wafer->wafer_id }}</span>
                                    <span class="text-xs">{{ $wafer->operator }}</span>
                                    <span class="text-xs">{{ $wafer->box }}</span>
                                    <span class="text-xs">{{ $wafer->machine }}</span>
                                    <span class="text-xs text-gray-500 truncate col-span-2">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                                </div>
                                @if($wafer->rejection->reject)
                                    <span class="text-xs font-normal text-red-500">Ausschuss: {{ $wafer->rejection->name }}</span>
                                @else
                                    <span class="text-xs font-normal text-green-600">Dieser Wafer ist in Ordnung</span>
                                @endif
                                @error('edit' . $wafer->id) <span class="text-xs mt-1 text-red-500"><i class="fal fa-circle-exclamation mr-1 text-red-500"></i><span class="text-gray-500">Konnte nicht bearbeiten:</span> {{ $message }}</span> @enderror
                                @if(session()->has('success' . $wafer->id)) <span class="text-xs mt-1 text-gray-500"><i class="fal fa-check mr-1 text-green-500"></i> Erfolgreich bearbeitet!</span> @enderror
                            </div>
                        </div>
                        <div class="flex w-full px-2 py-2 border-t bg-gray-50 items-center border-gray-200 gap-1" x-show="waferOpen && !waferEdit">
                            <i class="fal fa-cog mr-1"></i>
                            <a href="javascript:;" @click="waferEdit = true" class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i class="fal fa-pencil mr-1"></i> Wafer bearbeiten</a>
                            <!--<a href="javascript:;" class="bg-yellow-400 text-xs px-3 py-1 uppercase hover:bg-yellow-400/80 rounded-sm"><i class="fal fa-undo mr-1"></i> Nacharbeiten</a>
                            <a href="javascript:;" class="bg-orange-500 text-xs px-3 py-1 uppercase hover:bg-orange-500/80 rounded-sm text-white"><i class="fal fa-circle-xmark mr-1"></i> Eintrag als ungültig markieren</a>-->
                            <a href="javascript:;" wire:click="removeEntry({{ $wafer->id }})" class="bg-red-500 text-xs px-3 py-1 uppercase hover:bg-red-500/80 rounded-sm text-white"><i class="fal fa-trash mr-1"></i> Wafer löschen</a>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col justify-center items-center p-10">
                        <span class="text-lg font-bold text-red-500">Keine Wafer gefunden!</span>
                        <span class="text-sm text-gray-500">Es wurden keine Wafer in diesem Arbeitsschritt gefunden.</span>
                    </div>
                @endforelse
            </div>
            <div class="flex flex-col justify-center items-center p-10 animate-pulse text-center" wire:loading.delay.longer wire:target="search">
                <span class="text-lg font-bold text-red-500">Wafer werden geladen...</span><br>
                <span class="text-sm text-gray-500">Die Wafer werden geladen, wenn dieser Vorgang zu lange dauert bitte die Seite neu laden.</span>
            </div>
        </div>
    </div>
</div>
