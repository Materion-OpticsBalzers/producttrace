<?php
    use App\Models\Data\Wafer;
    use App\Models\Data\Order;
    use App\Models\Generic\Block;
    use App\Models\Data\Process;
    use App\Models\Generic\Rejection;
    use App\Models\Data\Serial;

    new class extends \Livewire\Volt\Component {
        public $block;
        public $order;
        public $prevBlock;
        public $nextBlock;

        public $search = '';
        public $searchField = 'wafer_id';

        public $selectedWafer = null;
        public $selectedRejection = 6;
        public $box = null;
        public $serial = null;

        public function mount() {
            $blockInfo = BlockHelper::getPrevAndNextBlock($this->order, $this->block->id);
            $this->prevBlock = $blockInfo->prev;
            $this->nextBlock = $blockInfo->next;
        }

        public function checkWafer($waferId) {
            if($waferId == '') {
                $this->addError('wafer', 'Die Wafernummer darf nicht leer sein!');
                return false;
            }

            $wafer = Wafer::find($waferId);

            if($wafer == null) {
                $this->addError('wafer', 'Dieser Wafer ist nicht vorhanden!');
                return false;
            }

            if($wafer->rejected) {
                if(!$this->order->wafer_check_ar || ($this->order->wafer_check_ar && \WaferHelper::waferWithBoxRejected($waferId, $this->box))) {
                    if ($this->nextBlock != null) {
                        $nextWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->nextBlock)->first();
                        if ($nextWafer == null) {
                            $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                            return false;
                        }
                    } else {
                        $this->addError('wafer', "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                        return false;
                    }
                }
            }

            if($wafer->reworked) {
                $this->addError('wafer', "Dieser Wafer wurde nachbearbeitet und kann nicht mehr verwendet werden!");
                return false;
            }

            if ($this->prevBlock != null && !$wafer->is_rework) {
                $prevWafer = Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->prevBlock)->first();
                if ($prevWafer == null) {
                    $this->addError('wafer', 'Dieser Wafer existiert nicht im vorherigen Schritt!');
                    return false;
                }
            }

            if (Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->block->id)->exists()) {
                $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
                return false;
            }

            return true;
        }

        public function addEntry($operator, $rejection) {
            $this->resetErrorBag();
            $error = false;

            if($operator == '') {
                $this->addError('operator', 'Der Operator darf nicht leer sein!');
                $error = true;
            }

            if($rejection == null) {
                $this->addError('rejection', 'Es muss ein Ausschussgrund abgegeben werden!');
                $error = true;
            }

            if($error)
                return false;

            if(!$this->checkWafer($this->selectedWafer)) {
                return false;
            }

            $rejection = Rejection::find($rejection);

            Process::create([
                'wafer_id' => $this->selectedWafer,
                'order_id' => $this->order->id,
                'block_id' => $this->block->id,
                'rejection_id' => $rejection->id,
                'operator' => $operator,
                'box' => $this->box,
                'date' => now()
            ]);

            if($rejection->reject) {
                Wafer::find($this->selectedWafer)->update([
                    'rejected' => 1,
                    'rejection_reason' => $rejection->name,
                    'rejection_position' => $this->block->name,
                    'rejection_avo' => $this->block->avo,
                    'rejection_order' => $this->order->id
                ]);
            }

            $this->selectedWafer = '';
            $this->selectedRejection = 6;
            session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
            $this->dispatch('saved');
        }

        public function updateEntry($entryId, $operator, $box, $rejection) {
            if($operator == '') {
                $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
                return false;
            }

            if($box == '') {
                $this->addError('edit' . $entryId, 'Box darf nicht leer sein!');
                return false;
            }

            $rejection = Rejection::find($rejection);
            $process = Process::find($entryId);
            $wafer = Wafer::find($process->wafer_id);

            if($wafer->rejected && $rejection->reject && $rejection->id != $process->rejection_id && !$process->rejection->reject){
                $this->addError('edit' . $entryId, "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
                return false;
            }

            if($rejection->reject) {
                $blockQ = Block::find($process->block_id);

                $wafer->update([
                    'rejected' => 1,
                    'rejection_reason' => $rejection->name,
                    'rejection_position' => $blockQ->name,
                    'rejection_avo' => $blockQ->avo,
                    'rejection_order' => $process->order_id
                ]);
            } else {
                if($process->rejection->reject) {
                    $wafer->update([
                        'rejected' => 0,
                        'rejection_reason' => null,
                        'rejection_position' => null,
                        'rejection_avo' => null,
                        'rejection_order' => null
                    ]);
                }
            }

            $process->update([
                'operator' => $operator,
                'box' => $box,
                'rejection_id' => $rejection->id
            ]);

            session()->flash('success' . $entryId);
        }

        public function removeEntry($entryId) {
            $process = Process::find($entryId);

            if ($process->rejection != null) {
                if ($process->wafer->rejected && $process->rejection->reject) {
                    Wafer::find($process->wafer_id)->update([
                        'rejected' => false,
                        'rejection_reason' => null,
                        'rejection_position' => null,
                        'rejection_avo' => null,
                        'rejection_order' => null
                    ]);
                }
            }

            $process->delete();
        }

        public function clear() {
            $wafers = Process::where('order_id', $this->order->id)->where('block_id', $this->block->id)->with('wafer');

            foreach ($wafers->lazy() as $wafer) {
                if($wafer->rejection != null) {
                    if ($wafer->wafer->rejected && $wafer->rejection->reject) {
                        Wafer::find($wafer->wafer_id)->update([
                            'rejected' => false,
                            'rejection_reason' => null,
                            'rejection_position' => null,
                            'rejection_avo' => null,
                            'rejection_order' => null
                        ]);
                    }
                }
            }

            $wafers->delete();
        }

        public function updateWafer($wafer, $box) {
            $this->selectedWafer = $wafer;
            $this->box = $box;
            $this->serial = Serial::where('wafer_id', $wafer)->first()->id;
        }

        public function with()
        {
            $wafers = Process::where('order_id', $this->order->id)->where('block_id', $this->block->id)->with('rejection')->orderBy('wafer_id', 'asc')->get();

            foreach($wafers as $wafer) {
                $wafer->serial = Serial::where('wafer_id', $wafer->wafer_id)->where('order_id', $this->order->id)->first();
            }

            if($this->search != '') {
                $searchField = $this->searchField;
                $wafers = $wafers->filter(function ($value, $key) use ($searchField) {
                    return stristr($value->$searchField, $this->search);
                });
            }

            $rejections = Rejection::find($this->block->rejections);

            if(!empty($rejections))
                $rejections = $rejections->sortBy('number');

            if($this->selectedWafer != '') {
                $sWafers = Process::where('block_id', $this->prevBlock)->where('order_id', $this->order->id)->where(function ($query) {
                    $query->where('wafer_id', $this->selectedWafer)->orWhere('wafer_id', $this->selectedWafer . '-r');
                })->orderBy('wafer_id', 'desc')->with('wafer')->lazy();

                if ($sWafers->count() > 0) {
                    $this->updateWafer($sWafers->get(0)->wafer_id, $sWafers->get(0)->box);
                }
            } else
                $sWafers = [];

            return compact('wafers', 'rejections', 'sWafers');
        }
    }
?>

<div class="flex flex-col bg-gray-100 w-full h-full z-[9] border-l border-gray-200 overflow-y-auto" x-data="">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8] bg-white sticky top-0">
        <span class="font-extrabold text-lg mr-2">{{ $block->avo }}</span>
        <span class="grow">{{ $block->name }}</span>
        @can('is-admin')
            @if($wafers->count() > 0)
                <a href="javascript:;" onclick="confirm('Bist du sicher das du alle Einträge löschen willst?') || event.stopImmediatePropagation()" wire:click="clear" class="hover:bg-gray-50 rounded-sm px-2 py-1 text-sm text-red-500 font-semibold mt-1"><i class="far fa-trash mr-1"></i> Alle Positionen Löschen</a>
            @endif
        @endcan
    </div>
    <div class="h-full bg-gray-100 flex z-[7]" x-data="{ hidePanel: $persist(false) }" :class="hidePanel ? '' : 'flex-col'">
        <a href="javascript:;" @click="hidePanel = false" class="h-full bg-white w-12 p-3 border-r border-gray-200 hover:bg-gray-50" x-show="hidePanel">
            <p class="transform rotate-90 font-bold w-full text-lg whitespace-nowrap"><i class="far fa-chevron-up mr-3"></i> Eintrag hinzufügen</p>
        </a>
        <div class="w-full h-max flex flex-col bg-white border-r border-gray-200 px-8 pt-3 pb-4" x-show="!hidePanel">
            <h1 class="text-base font-bold flex justify-between items-center">
                Eintrag hinzufügen
                <a href="javascript:;" @click="hidePanel = true" class="px-3 py-1 text-sm rounded-sm font-semibold hover:bg-gray-50"><i class="far fa-eye mr-1"></i> Einträge anzeigen ({{ $wafers->count() }})</a>
            </h1>
            <div class="flex flex-col h-full relative gap-2 mt-3" x-data="{ operator: {{ auth()->user()->personnel_number }}, rejection: @entangle('selectedRejection') }">
                <div class="w-full h-full absolute" wire:loading wire:target="updateWafer">
                    <div class="w-full h-full flex justify-center absolute items-center z-[5]">
                        <h1 class="text-[#0085CA] font-bold text-2xl"><i class="far fa-spinner animate-spin"></i> Daten von Wafer werden geladen...</h1>
                    </div>
                    <div class="w-full h-full bg-white opacity-80 absolute z-[4]"></div>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Wafer ID *:</label>
                    <div class="flex flex-col w-full relative" x-data="{ show: false, search: '' }" @click.away="show = false">
                        <div class="flex flex-col">
                            <div class="flex">
                                <input type="text" wire:model.live.debounce.500ms="selectedWafer" id="wafer" tabindex="1" onfocus="this.setSelectionRange(0, this.value.length)" @focus="show = true" class="w-full bg-gray-100 @error('wafer') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror font-semibold text-sm" placeholder="Wafer ID eingeben oder scannen..."/>
                            </div>
                            @if(session()->has('waferScanned')) <span class="text-xs mt-1 text-green-600">Gescannter Wafer geladen!</span> @endif
                        </div>
                        <div class="shadow-lg rounded-sm absolute w-full mt-10 border border-gray-300 bg-gray-200 overflow-y-auto max-h-60" x-show="show" x-transition>
                            <div class="flex flex-col divide-y divide-gray-300" wire:loading.remove>
                                <div class="px-2 py-1 text-xs text-gray-500">{{ sizeof($sWafers) }} Wafer @if($prevBlock != null) von vorherigem Schritt @endif</div>
                                @forelse($sWafers as $wafer)
                                    <a href="javascript:;" wire:click="updateWafer('{{ $wafer->wafer_id }}', '{{ $wafer->box }}')" @click="show = false" class="flex items-center px-2 py-1 text-sm hover:bg-gray-100">
                                        @if($wafer->wafer->rejected && !$wafer->wafer->reworked)
                                            <i class="far fa-ban text-red-500 mr-2"></i>
                                        @elseif($wafer->wafer->reworked)
                                            <i class="far fa-exclamation-triangle text-orange-500 mr-2"></i>
                                        @else
                                            <i class="far fa-check text-green-600 mr-2"></i>
                                        @endif
                                        <div class="flex flex-col">
                                            {{ $wafer->wafer_id }}
                                            <span class="text-xs text-gray-500"><i class="fal fa-box-open"></i> Box: {{ $wafer->box }}</span>
                                            @if($wafer->wafer->rejected && !$wafer->wafer->reworked)
                                                <span class="text-xs text-red-500 italic"><b>{{ $wafer->wafer->rejection_reason }}</b> in {{ $wafer->wafer->rejection_order }} <i class="fal fa-arrow-right"></i> {{ $wafer->wafer->rejection_avo }} - {{ $wafer->wafer->rejection_position }} </span>
                                            @elseif($wafer->wafer->reworked)
                                                <span class="text-xs text-orange-500 italic">Nachbearbeitet </span>
                                            @elseif($wafer->wafer->is_rework)
                                                <span class="text-xs font-normal text-orange-500">Dieser Wafer ist ein Nacharbeits Wafer</span>
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
                    @error('wafer')
                        <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                            <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                            <span class="font-semibold">{{ $message }}</span>
                        </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Operator *:</label>
                    <input x-model="operator" onfocus="this.setSelectionRange(0, this.value.length)" type="text" class="bg-gray-100 @error('operator') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold" tabindex="2" placeholder="Operator"/>
                    @error('operator')
                        <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                            <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                            <span class="font-semibold">{{ $message }}</span>
                        </div>
                    @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="text-sm mb-1 text-gray-500">Box ID (Optional):</label>
                        <input wire:model="box" onfocus="this.setSelectionRange(0, this.value.length)" type="text" class="bg-gray-100 @error('box') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold" tabindex="3" placeholder="Box ID"/>
                        @error('box')
                        <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                            <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                            <span class="font-semibold">{{ $message }}</span>
                        </div>
                        @enderror
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm mb-1 text-gray-500">Serial:</label>
                        <input disabled wire:model.live="serial" type="text" class="bg-gray-200 border-0 rounded-sm text-sm font-semibold" placeholder="Serial"/>
                    </div>
                </div>

                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Ausschussgrund *:</label>
                    <fieldset class="grid grid-cols-2 gap-0.5">
                        @forelse($rejections as $rejection)
                            <label class="flex px-3 py-3 @if($rejection->reject) bg-red-100/50 @else bg-green-100/50 @endif rounded-sm items-center">
                                <input x-model="rejection" value="{{ $rejection->id }}" type="radio" class="text-[#0085CA] border-gray-300 rounded-sm focus:ring-[#0085CA] mr-2" name="rejection">
                                <span class="text-sm">{{ $rejection->name }}</span>
                            </label>
                        @empty
                            <span class="text-xs text-red-500 font-semibold">Ausschussgründe wurden noch nicht definiert...</span>
                        @endforelse
                    </fieldset>
                    @error('rejection')
                        <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                            <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                            <span class="font-semibold">{{ $message }}</span>
                        </div>
                    @enderror
                </div>
                @if(session()->has('success')) <span class="mt-1 text-xs font-semibold text-green-600">Eintrag wurde erfolgreich gespeichert</span> @endif
                <button type="submit" @click="$wire.addEntry(operator, rejection);" class="bg-[#0085CA] hover:bg-[#0085CA]/80 rounded-sm px-3 py-4 text-sm uppercase text-white text-left" tabindex="4">
                    <span wire:loading.remove wire:target="addEntry">Eintrag Speichern</span>
                    <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                </button>
            </div>
        </div>
        <div class="w-full px-4 py-3 flex flex-col pb-4" x-show="hidePanel" x-cloak>
            <h1 class="text-base font-bold">Eingetragene Wafer ({{ $wafers->count() }})</h1>
            <div class="flex gap-4">
                <select wire:model="searchField" class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold w-max shadow-sm border-0 focus:ring-[#0085CA]">
                    <option value="wafer_id">Wafer ID</option>
                    <option value="ar_box">AR Box ID</option>
                </select>
                <input type="text" wire:model.blur="search" onfocus="this.setSelectionRange(0, this.value.length)" class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]" placeholder="Wafer durchsuchen..." />
            </div>
            <div class="flex flex-col gap-1 mt-2" wire:loading.remove.delay.longer wire:target="search">
                <div class="px-2 py-1 rounded-sm grid grid-cols-4 items-center justify-between bg-gray-200 shadow-sm mb-1">
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Serial</span>
                    <span class="text-sm font-bold"><i class="fal fa-user mr-1"></i> Operator</span>
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Box ID</span>
                    <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                </div>
                @forelse($wafers as $wafer)
                    <div class="bg-white border @if($wafer->rejection->reject) border-red-500/50 @elseif($wafer->reworked || $wafer->wafer->reworked) border-orange-500/50 @else border-green-600/50 @endif flex flex-col rounded-sm hover:bg-gray-50 items-center" x-data="{ waferOpen: false, waferEdit: false }">
                        <div class="flex flex-col px-2 py-2 w-full" x-show="waferEdit" x-trap="waferEdit" x-data="{ operator: '{{ $wafer->operator }}', box: '{{ $wafer->box }}', rejection: {{ $wafer->rejection_id }} }">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-gray-500">Wafer (Nicht änderbar)</label>
                                <input disabled type="text" value="{{ $wafer->wafer_id }}" class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"/>
                                <label class="text-xs text-gray-500">Operator</label>
                                <input x-model="operator" type="text" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold" placeholder="Operator"/>
                                <label class="text-xs text-gray-500">Box</label>
                                <input x-model="box" type="text" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold" placeholder="Box ID"/>
                            </div>
                            <label class="text-xs text-gray-500 mt-1">Ausschussgrund</label>
                            <select x-model="rejection" class="bg-gray-200 rounded-sm border-0 mt-1 focus:ring-[#0085CA] text-xs font-semibold">
                                @forelse($rejections as $rejection)
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
                                <div class="grid grid-cols-4 items-center">
                                    <span class="text-sm font-semibold">{{ $wafer->wafer_id }}  @if($wafer->reworked || $wafer->wafer->reworked) (Nacharbeit) @endif</span>
                                    <span class="text-xs">{{ $wafer->serial->id ?? '?' }}</span>
                                    <span class="text-xs">{{ $wafer->operator }}</span>
                                    <span class="text-xs">{{ $wafer->box }}</span>
                                    <span class="text-xs text-gray-500 truncate">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                                </div>
                                @if($wafer->rejection->reject ?? false)
                                    <span class="text-xs font-normal text-red-500">Ausschuss: {{ $wafer->rejection->name }}</span>
                                @elseif($wafer->reworked || $wafer->wafer->reworked)
                                    <span class="text-xs font-normal text-orange-500">Wafer wurde Nachbearbeitet</span>
                                @else
                                    <span class="text-xs font-normal text-green-600">Dieser Wafer ist in Ordnung</span>
                                @endif
                                @error('edit' . $wafer->id) <span class="text-xs mt-1 text-red-500"><i class="fal fa-circle-exclamation mr-1 text-red-500"></i><span class="text-gray-500">Konnte nicht bearbeiten:</span> {{ $message }}</span> @enderror
                                @if(session()->has('success' . $wafer->id)) <span class="text-xs mt-1 text-gray-500"><i class="fal fa-check mr-1 text-green-500"></i> Erfolgreich bearbeitet!</span> @endif
                            </div>
                        </div>
                        <div class="flex w-full px-2 py-2 border-t bg-gray-50 items-center border-gray-200 gap-1" x-show="waferOpen && !waferEdit">
                            <i class="fal fa-cog mr-1"></i>
                            <a href="{{ route('wafer.show', ['wafer' => $wafer->wafer_id]) }}" target="_blank" class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i class="fal fa-search mr-1"></i> Wafer verfolgen</a>
                            <a href="javascript:;" @click="waferEdit = true" class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i class="fal fa-pencil mr-1"></i> Wafer bearbeiten</a>
                            <a href="javascript:;" onclick="confirm('Willst du diesen Wafer wirklich löschen?') || event.stopImmediatePropagation()" wire:click="removeEntry({{ $wafer->id }})" class="bg-red-500 text-xs px-3 py-1 uppercase hover:bg-red-500/80 rounded-sm text-white"><i class="fal fa-trash mr-1"></i> Wafer löschen</a>
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
    <script>
        document.addEventListener('saved', (e) => {
            document.getElementById('wafer').focus()
        });
    </script>
</div>
