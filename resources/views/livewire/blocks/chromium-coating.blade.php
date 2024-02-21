<?php

use App\Models\Data\Wafer;
use App\Models\Data\Order;
use App\Models\Generic\Block;
use App\Models\Data\Process;
use App\Models\Generic\Rejection;
use App\Models\Data\Serial;
use Illuminate\Database\QueryException;

new class extends \Livewire\Volt\Component {
    public $block;
    public $order;
    public $prevBlock;
    public $nextBlock;

    public $search = '';
    public $searchField = 'wafer_id';
    public $machine = '';
    public $box = null;
    public $batch = '';
    public $calculatedPosition = 'Aussen';

    public string $selectedWafer = '';
    public string $searchWafer = '';
    public $selectedRejection = 6;

    public function mount()
    {
        $blockInfo = BlockHelper::getPrevAndNextBlock($this->order, $this->block->id);
        $this->prevBlock = $blockInfo->prev;
        $this->nextBlock = $blockInfo->next;
    }

    public function checkWafer($waferId)
    {
        if ($waferId == '') {
            $this->addError('wafer', 'Die Wafernummer darf nicht leer sein!');
            return false;
        }

        $wafer = Wafer::find($waferId);

        if ($wafer == null) {
            $this->addError('wafer', 'Dieser Wafer ist nicht vorhanden!');
            return false;
        }

        if ($wafer->rejected) {
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

        if ($wafer->reworked) {
            $this->addError('wafer', "Dieser Wafer wurde nachbearbeitet und kann nicht mehr verwendet werden!");
            return false;
        }

        if (Process::where('wafer_id', $wafer->id)->where('order_id', $this->order->id)->where('block_id', $this->block->id)->exists()) {
            $this->addError('wafer', 'Dieser Wafer wurde schon verwendet!');
            return false;
        }

        return true;
    }

    public function addEntry($operator, $rejection, $rework = false)
    {
        $this->resetErrorBag();
        $error = false;

        if ($operator == '') {
            $this->addError('operator', 'Der Operator darf nicht leer sein!');
            $error = true;
        }

        if ($this->box == '') {
            $this->addError('box', 'Die Box ID Darf nicht leer sein!');
            $error = true;
        }

        if ($this->machine == '') {
            $this->addError('machine', 'Anlagennummer darf nicht leer sein!');
            $error = true;
        }

        if ($this->batch == '') {
            $this->addError('lot', 'Die Chargennummer darf nicht leer sein!');
            $error = true;
        }

        if ($error)
            return false;

        if (!$this->checkWafer($this->selectedWafer)) {
            return false;
        }

        $rejection = Rejection::find($rejection);

        $process = Process::create([
            'wafer_id' => $this->selectedWafer,
            'order_id' => $this->order->id,
            'block_id' => $this->block->id,
            'operator' => $operator,
            'box' => $this->box,
            'machine' => $this->machine,
            'lot' => $this->batch,
            'position' => $this->calculatedPosition,
            'rejection_id' => $rejection->id,
            'date' => now()
        ]);

        if ($rejection->reject) {
            Wafer::find($this->selectedWafer)->update([
                'rejected' => 1,
                'rejection_reason' => $rejection->name,
                'rejection_position' => $this->block->name,
                'rejection_avo' => $this->block->avo,
                'rejection_order' => $this->order->id
            ]);
        }

        if ($rework)
            $this->rework($process);

        $this->selectedWafer = '';
        $this->searchWafer = '';
        $this->selectedRejection = 6;
        session()->flash('success', 'Eintrag wurde erfolgreich gespeichert!');
        $this->dispatch('saved');
    }

    public function updateEntry($entryId, $operator, $box, $lot, $machine, $position, $rejection)
    {
        $this->resetErrorBag();

        if ($operator == '') {
            $this->addError('edit' . $entryId, 'Operator darf nicht leer sein!');
            return false;
        }

        if ($box == '') {
            $this->addError('edit' . $entryId, 'Box darf nicht leer sein!');
            return false;
        }

        if ($lot == '') {
            $this->addError('edit' . $entryId, 'Die Charge darf nicht leer sein!');
            return false;
        }

        $rejection = Rejection::find($rejection);
        $process = Process::find($entryId);
        $wafer = Wafer::find($process->wafer_id);

        if ($wafer->rejected && $rejection->reject && $rejection->id != $process->rejection_id && !$process->rejection->reject) {
            $this->addError('edit' . $entryId, "Dieser Wafer wurde in " . $wafer->rejection_order . " -> " . $wafer->rejection_avo . " " . $wafer->rejection_position . " als Ausschuss markiert.");
            return false;
        }

        if ($rejection->reject) {
            $wafer->update([
                'rejected' => 1,
                'rejection_reason' => $rejection->name,
                'rejection_position' => $this->block->name,
                'rejection_avo' => $this->block->avo,
                'rejection_order' => $this->order->id
            ]);
        } else {
            if($wafer->rejection_avo == $this->block->avo && $wafer->rejection_order == $this->order->id) {
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
            'machine' => $machine,
            'lot' => $lot,
            'position' => $position
        ]);

        session()->flash('success' . $entryId);
    }

    public function removeEntry($entryId)
    {
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

        if ($process->reworked) {
            Wafer::find($process->wafer_id)->update([
                'reworked' => false
            ]);
        }

        $process->delete();
    }

    public function clear()
    {
        $wafers = Process::where('order_id', $this->order->id)->where('block_id', $this->block->id)->with('wafer');

        foreach ($wafers->lazy() as $wafer) {
            if ($wafer->rejection != null) {
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

    public function updated($name)
    {
        if ($name == 'box') {
            try {
                $data = DB::connection('sqlsrv_eng')->select("SELECT TOP 1 identifier, batch FROM BAKCr_chargenprotokoll
                LEFT JOIN machine ON machine.id = BAKCr_chargenprotokoll.machine_id
                WHERE box_id = '{$this->box}'");
            } catch (QueryException $ex) {
                $data = [];
                $this->addError('lot', 'Chromdaten konnten für diese Box nicht gefunden werden!');
            }

            if (!empty($data)) {
                $this->machine = $data[0]->identifier;
                $this->batch = $data[0]->batch;
            } else {
                $this->machine = '';
                $this->batch = '';
                $this->addError('lot', 'Chromdaten konnten für diese Box nicht gefunden werden!');
            }
        }
    }

    public function updateWafer($wafer, $isRework = false, $box = null)
    {
        $this->selectedWafer = $wafer;

        if ($box && !$isRework) {
            $this->box = $box;
            $this->updated('box');
        }
    }

    public function rework(Process $process)
    {
        $rWafer = Wafer::find($process->wafer_id . '-r');
        if ($rWafer != null) {
            $process->update(['reworked' => true]);

            $wafer = Wafer::find($process->wafer_id);
            $wafer->update(['reworked' => true]);
        } else {
            $process->update(['reworked' => true]);

            $wafer = Wafer::find($process->wafer_id);
            $wafer->update(['reworked' => true]);

            $newWafer = $wafer->replicate();
            $newWafer->id = $wafer->id . '-r';
            $newWafer->reworked = false;
            $newWafer->is_rework = true;
            $newWafer->rejected = false;
            $newWafer->rejection_reason = null;
            $newWafer->rejection_position = null;
            $newWafer->rejection_avo = null;
            $newWafer->rejection_order = null;
            $newWafer->save();
        }
    }


    public function with()
    {
        $wafers = Process::where('order_id', $this->order->id)->where('block_id', $this->block->id)->with('rejection')->with('wafer')->orderBy('wafer_id', 'asc')->get();

        if ($this->search != '') {
            $searchField = $this->searchField;
            $wafers = $wafers->filter(function ($value, $key) use ($searchField) {
                return stristr($value->$searchField, $this->search);
            });
        }

        $rejections = Rejection::find($this->block->rejections);

        if (!empty($rejections))
            $rejections = $rejections->sortBy('number');

        $waferInfo = null;
        if($this->selectedWafer != '') {
            $waferInfo = Wafer::find($this->selectedWafer);
        }

        $searchedInAll = false;
        if ($this->searchWafer != '') {
            $sWafers = Process::where('block_id', $this->prevBlock)->where('order_id', $this->order->id)->where(function ($query) {
                $query->where('wafer_id', $this->searchWafer . '-r')->orWhere('wafer_id', $this->searchWafer);
            })->orderBy('wafer_id', 'desc')->with('wafer')->get();

            if ($sWafers->count() == 0) {
                $sWafers = Wafer::where('id', 'like', "%{$this->searchWafer}%")->orderBy('id', 'desc')->limit(28)->get();
                $searchedInAll = true;
            } else {
                if($this->selectedWafer != $this->searchWafer) {
                    $this->updateWafer($sWafers->get(0)->wafer_id, $sWafers->get(0)->wafer->is_rework, $sWafers->get(0)->box);
                }
            }
        } else
            $sWafers = [];

        $currentBoxWaferCount = $wafers->where('box', $this->box)->where('wafer.reworked', false)->count();
        if ($currentBoxWaferCount >= 9 && $currentBoxWaferCount < 13)
            $this->calculatedPosition = 'Mitte';
        elseif ($currentBoxWaferCount >= 13)
            $this->calculatedPosition = 'Zentrum';
        else
            $this->calculatedPosition = 'Aussen';

        return compact(['waferInfo', 'wafers', 'sWafers', 'searchedInAll', 'rejections']);
    }
}
?>


<div class="flex flex-col bg-gray-100 w-full h-full z-[9] border-l border-gray-200 overflow-y-auto">
    <div
        class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center bg-white z-[8] sticky top-0">
        <span class="font-extrabold text-lg mr-2">{{ $block->avo }}</span>
        <span class="grow">{{ $block->name }}</span>
        @can('is-admin')
            @if($wafers->count() > 0)
                <a href="javascript:;"
                   onclick="confirm('Willst du diesen Wafer wirklich löschen?') || event.stopImmediatePropagation()"
                   wire:click="clear()"
                   class="hover:bg-gray-50 rounded-sm px-2 py-1 text-sm text-red-500 font-semibold mt-1"><i
                        class="far fa-trash mr-1"></i> Alle Positionen Löschen</a>
            @endif
        @endcan
    </div>
    <div class="h-full bg-gray-100 flex z-[7]" x-data="{ hidePanel: $persist(false) }"
         :class="hidePanel ? '' : 'flex-col'">
        <a href="javascript:;" @click="hidePanel = false"
           class="h-full bg-white w-12 p-3 border-r border-gray-200 hover:bg-gray-50" x-show="hidePanel">
            <p class="transform rotate-90 font-bold w-full text-lg whitespace-nowrap"><i
                    class="far fa-chevron-up mr-3"></i> Eintrag hinzufügen</p>
        </a>
        <div class="w-full h-max flex flex-col bg-white border-r border-gray-200 px-8 pt-3 pb-4" x-show="!hidePanel">
            <h1 class="text-base font-bold flex justify-between items-center">
                Eintrag hinzufügen
                <a href="javascript:;" @click="hidePanel = true"
                   class="px-3 py-1 text-sm rounded-sm font-semibold hover:bg-gray-50"><i class="far fa-eye mr-1"></i>
                    Einträge anzeigen ({{ $wafers->count() }})</a>
            </h1>
            <div class="flex mt-3 gap-4">
                <div class="flex items-center gap-2">
                    <span class="bg-orange-100 w-5 h-5 rounded-md"></span> <span class="text-sm">= Pflichtfeld</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-gray-200 w-5 h-5 rounded-md"></span> <span class="text-sm">= Kein Pflichtfeld</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="bg-gray-200/50 w-5 h-5 rounded-md"></span> <span class="text-sm">= Read Only</span>
                </div>
            </div>
            <div class="flex flex-col h-full relative gap-2 mt-3"
                 x-data="{ wafer: '', operator: {{ auth()->user()->personnel_number }}, rejection: @entangle('selectedRejection')  }">
                <div class="w-full h-full absolute" wire:loading wire:target="updateWafer">
                    <div class="w-full h-full flex justify-center absolute items-center z-[5]">
                        <h1 class="text-[#0085CA] font-bold text-2xl"><i class="far fa-spinner animate-spin"></i> Daten
                            von Wafer werden geladen...</h1>
                    </div>
                    <div class="w-full h-full bg-white opacity-80 absolute z-[4]"></div>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Wafer ID *:</label>
                    <div class="flex flex-col w-full relative" x-data="{ show: false, search: '' }"
                         @click.away="show = false">
                        <div class="flex flex-col">
                            <div class="flex">
                                <input type="text" id="wafer" wire:model.live.debounce.500ms="searchWafer"
                                       tabindex="1"
                                       onfocus="this.setSelectionRange(0, this.value.length)" @focus="show = true"
                                       class="w-full bg-orange-100 @error('wafer') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror font-semibold text-sm "
                                       placeholder="Wafer ID eingeben oder scannen..."/>
                            </div>
                            @if(session()->has('waferScanned'))
                                <span class="text-xs mt-1 text-green-600">Gescannter Wafer geladen!</span>
                            @endif
                        </div>
                        <div
                            class="shadow-lg rounded-sm absolute w-full mt-10 border border-gray-300 bg-gray-200 overflow-y-auto max-h-60"
                            x-show="show" x-transition>
                            <div class="flex flex-col divide-y divide-gray-300" wire:loading.remove>
                                <div class="px-2 py-1 text-xs text-gray-500">{{ sizeof($sWafers) }}
                                    Wafer @if($prevBlock != null)
                                        von vorherigem Schritt
                                    @endif</div>
                                @forelse($sWafers as $wafer)
                                    @php
                                        if(!$searchedInAll) {
                                            $wafer->wafer->box = $wafer->box;
                                            $wafer = $wafer->wafer;
                                        } else {
                                            $wafer->box = null;
                                        }
                                    @endphp
                                    <a href="javascript:" tabindex="1"
                                       wire:click="updateWafer('{{ $wafer->id }}', {{ $wafer->is_rework }}, '{{ $wafer->box }}')"
                                       @click="show = false"
                                       class="flex items-center px-2 py-1 text-sm focus:border-none focus:bg-gray-100 hover:bg-gray-100">
                                        @if($wafer->rejected && !$wafer->reworked)
                                            <i class="far fa-ban text-red-500 mr-2"></i>
                                        @elseif($wafer->reworked)
                                            <i class="far fa-exclamation-triangle text-orange-500 mr-2"></i>
                                        @else
                                            <i class="far fa-check text-green-600 mr-2"></i>
                                        @endif
                                        <div class="flex flex-col">
                                            <span class="font-semibold">{{ $wafer->id }}</span>
                                            @if($wafer->rejected && !$wafer->reworked)
                                                <span
                                                    class="text-xs text-red-500 italic"><b>{{ $wafer->rejection_reason }}</b> in {{ $wafer->rejection_order }} <i
                                                        class="fal fa-arrow-right"></i> {{ $wafer->rejection_avo }} - {{ $wafer->rejection_position }} </span>
                                            @elseif($wafer->reworked)
                                                <span class="text-xs text-orange-500 italic">Nachbearbeitet </span>
                                            @elseif($wafer->is_rework)
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
                            <div class="flex w-full px-2 py-1 text-sm" wire:loading><i
                                    class="fal fa-refresh animate-spin mr-1"></i> Wafer werden geladen
                            </div>
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
                    <input x-model="operator" onfocus="this.setSelectionRange(0, this.value.length)" type="text"
                           tabindex="2"
                           class="bg-orange-100 @error('operator') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold"
                           placeholder="Operator"/>
                    @error('operator')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm text-gray-500">Box ID *:</label>
                    <input wire:model.live.debounce.1000ms="box" onfocus="this.setSelectionRange(0, this.value.length)" type="text"
                           tabindex="3"
                           class="mt-1 bg-orange-100 @error('box') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold"
                           placeholder="Box ID"/>
                    @error('box')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm text-gray-500">Chromcharge *:</label>
                    <span class="text-xs font-light italic">Die Chromcharge wird automatisch gezogen, kann aber geändert werden</span>
                    <input wire:model="batch" onfocus="this.setSelectionRange(0, this.value.length)" type="text"
                           tabindex="4"
                           class="mt-1 bg-orange-100 @error('lot') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold"
                           placeholder="Chromcharge"/>
                    @error('lot')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm text-gray-500">Anlagennummer *:</label>
                    <span
                        class="text-xs font-light italic">Anlage wird automatisch gezogen, kann jedoch geändert werden</span>
                    <select wire:model="machine" tabindex="5"
                            class="mt-1 bg-orange-100 @error('machine') border-1 border-red-500/40 rounded-t-sm @else border-0 rounded-sm @enderror text-sm font-semibold">
                        <option value="" disabled>Nicht gefunden</option>
                        <option value="BAKCr51">BAKCr51</option>
                        <option value="BAKCr52">BAKCr52</option>
                    </select>
                    @error('machine')
                    <div class="bg-red-500/20 text-red-500 flex items-center px-2 py-0.5 rounded-b-sm text-xs">
                        <i class="far fa-exclamation-circle mr-1 animate-pulse"></i>
                        <span class="font-semibold">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
                <div class="flex flex-col">
                    <label class="text-sm text-gray-500">Position *:</label>
                    <span class="text-xs font-light italic">  Position wird automatisch berechnet, kann jedoch geändert werden</span>
                    <select wire:model="calculatedPosition" tabindex="6"
                            class="mt-1 bg-orange-100 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold">
                        <option value="Aussen">Aussen</option>
                        <option value="Mitte">Mitte</option>
                        <option value="Zentrum">Zentrum</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm mb-1 text-gray-500">Ausschussgrund *:</label>
                    <fieldset class="grid grid-cols-2 gap-0.5">
                        @forelse($rejections as $rejection)
                            <label
                                class="flex px-3 py-3 @if($rejection->reject) bg-red-100/50 @else bg-green-100/50 @endif rounded-sm items-center">
                                <input x-model="rejection" value="{{ $rejection->id }}" type="radio"
                                       class="text-[#0085CA] border-gray-300 rounded-sm focus:ring-[#0085CA] mr-2"
                                       name="rejection">
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
                @if(session()->has('success'))
                    <span class="mt-1 text-xs font-semibold text-green-600">Eintrag wurde erfolgreich gespeichert</span>
                @endif
                <div class="flex gap-2">
                    @if($waferInfo && !$waferInfo->reworked)
                        <button type="submit" @click="$wire.addEntry(operator, rejection, true)" x-show="rejection != 6"
                                class="bg-orange-500 w-max whitespace-nowrap hover:bg-orange-500/80 rounded-sm px-3 py-4 text-sm uppercase text-white text-left"
                                tabindex="7">
                            <span wire:loading.remove wire:target="addEntry">Eintrag als Nacharbeit Speichern</span>
                            <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                        </button>
                    @endif
                    <button type="submit" @click="$wire.addEntry(operator, rejection)"
                            class="bg-[#0085CA] w-full hover:bg-[#0085CA]/80 rounded-sm px-3 py-4 text-sm uppercase text-white text-left"
                            tabindex="7">
                        <span wire:loading.remove wire:target="addEntry">Eintrag Speichern</span>
                        <span wire:loading wire:target="addEntry"><i class="fal fa-save animate-pulse mr-1"></i> Eintrag wird gespeichert...</span>
                    </button>
                </div>

            </div>
        </div>
        <div class="w-full px-4 py-3 flex flex-col" x-show="hidePanel" x-cloak>
            <h1 class="text-base font-bold">Eingetragene Wafer ({{ $wafers->count() }})</h1>
            <div class="flex gap-4">
                <select wire:model="searchField"
                        class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold w-max shadow-sm border-0 focus:ring-[#0085CA]">
                    <option value="wafer_id">Wafer ID</option>
                    <option value="box">Box ID</option>
                </select>
                <input type="text" wire:model.blur="search" onfocus="this.setSelectionRange(0, this.value.length)"
                       class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]"
                       placeholder="Wafer durchsuchen..."/>
            </div>
            <div class="flex flex-col gap-1 mt-2 pb-4" wire:loading.remove.delay.longer wire:target="search">
                <div
                    class="px-2 py-1 rounded-sm grid grid-cols-7 items-center justify-between bg-gray-200 shadow-sm mb-1">
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                    <span class="text-sm font-bold"><i class="fal fa-user mr-1"></i> Operator</span>
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Box ID</span>
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Charge</span>
                    <span class="text-sm font-bold"><i class="fal fa-robot mr-1"></i> Anlage</span>
                    <span class="text-sm font-bold"><i class="fal fa-map-marker-alt mr-1"></i> Position</span>
                    <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                </div>
                @forelse($wafers as $wafer)
                    <div
                        class="bg-white border @if($wafer->reworked || $wafer->wafer->reworked) border-orange-500/50 @else border-green-600/50 @endif flex flex-col rounded-sm hover:bg-gray-50 items-center"
                        x-data="{ waferOpen: false, waferEdit: false }">
                        <div class="flex flex-col px-2 py-2 w-full" x-show="waferEdit" x-trap="waferEdit"
                             x-data="{ operator: '{{ $wafer->operator }}', box: '{{ $wafer->box }}', lot: '{{ $wafer->lot }}', machine: '{{ $wafer->machine }}', position: '{{ $wafer->position }}', rejection: {{ $wafer->rejection_id ?? 6 }} }">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs text-gray-500">Wafer (Nicht änderbar)</label>
                                <input disabled type="text" value="{{ $wafer->wafer_id }}"
                                       class="bg-gray-100 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"/>
                                <label class="text-xs text-gray-500">Operator</label>
                                <input x-model="operator" type="text"
                                       class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                       placeholder="Operator"/>
                                <label class="text-xs text-gray-500">Box</label>
                                <input x-model="box" type="text"
                                       class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                       placeholder="Box ID"/>
                                <label class="text-xs text-gray-500">Charge</label>
                                <input x-model="lot" type="text"
                                       class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-xs font-semibold"
                                       placeholder="Charge"/>
                                <label class="text-xs text-gray-500">Anlage</label>
                                <select x-model="machine"
                                        class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold">
                                    <option value="BAKCr51">BAKCr51</option>
                                    <option value="BAKCr52">BAKCr52</option>
                                </select>
                                <label class="text-xs text-gray-500">Position</label>
                                <select x-model="position"
                                        class="mt-1 bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] text-sm font-semibold">
                                    <option value="Aussen">Aussen</option>
                                    <option value="Mitte">Mitte</option>
                                    <option value="Zentrum">Zentrum</option>
                                </select>
                                <label class="text-xs text-gray-500 mt-1">Ausschussgrund</label>
                                <select x-model="rejection"
                                        class="bg-gray-200 rounded-sm border-0 mt-1 focus:ring-[#0085CA] text-xs font-semibold">
                                    @forelse($rejections->lazy() as $rejection)
                                        <option value="{{ $rejection->id }}">{{ $rejection->name }}</option>
                                    @empty
                                        <option value="" disabled>Keine Ausschussgründe definiert</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="flex gap-1 mt-2">
                                <a href="javascript:;"
                                   @click="$wire.updateEntry({{ $wafer->id }}, operator, box, lot, machine, position, rejection); waferEdit = false"
                                   class="bg-[#0085CA] hover:bg-[#0085CA]/80 text-white rounded-sm px-2 py-1 uppercase text-xs">Speichern</a>
                                <a href="javascript:;" @click="waferEdit = false"
                                   class="bg-red-500 hover:bg-red-500/80 text-white rounded-sm px-2 py-1 uppercase text-xs">Abbrechen</a>
                            </div>
                        </div>
                        <div class="flex w-full px-2 py-1 cursor-pointer items-center" @click="waferOpen = !waferOpen"
                             x-show="!waferEdit">
                            <i class="fal fa-chevron-down mr-2" x-show="!waferOpen"></i>
                            <i class="fal fa-chevron-up mr-2" x-show="waferOpen"></i>
                            <div class="flex flex-col grow">
                                <div class="grid grid-cols-7 items-center">
                                    <span
                                        class="text-sm font-semibold">{{ $wafer->wafer_id }} @if($wafer->reworked || $wafer->wafer->reworked)
                                            (Nacharbeit)
                                        @endif</span>
                                    <span class="text-xs">{{ $wafer->operator }}</span>
                                    <span class="text-xs">{{ $wafer->box }}</span>
                                    <span class="text-xs">{{ $wafer->lot }}</span>
                                    <span class="text-xs">{{ $wafer->machine }}</span>
                                    <span class="text-xs">{{ $wafer->position }}</span>
                                    <span
                                        class="text-xs text-gray-500 truncate">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                                </div>
                                @if($wafer->rejection->reject ?? false)
                                    <span
                                        class="text-xs font-normal text-red-500">Ausschuss: {{ $wafer->rejection->name }}</span>
                                    @if($wafer->reworked)
                                        <span class="text-xs text-orange-500 italic">Wafer wurde Nachbearbeitet </span>
                                    @endif
                                @elseif($wafer->reworked || $wafer->wafer->reworked)
                                    <span class="text-xs font-normal text-orange-500">Wafer wurde Nachbearbeitet</span>
                                @else
                                    <span class="text-xs font-normal text-green-600">Dieser Wafer ist in Ordnung</span>
                                @endif
                                @error('edit' . $wafer->id) <span class="text-xs mt-1 text-red-500"><i
                                        class="fal fa-circle-exclamation mr-1 text-red-500"></i><span
                                        class="text-gray-500">Konnte nicht bearbeiten:</span> {{ $message }}</span> @enderror
                                @if(session()->has('success' . $wafer->id))
                                    <span class="text-xs mt-1 text-gray-500"><i
                                            class="fal fa-check mr-1 text-green-500"></i> Erfolgreich bearbeitet!</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex w-full px-2 py-2 border-t bg-gray-50 items-center border-gray-200 gap-1"
                             x-show="waferOpen && !waferEdit">
                            <i class="fal fa-cog mr-1"></i>
                            <a href="{{ route('wafer.show', ['wafer' => $wafer->wafer_id]) }}" target="_blank"
                               class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i
                                    class="fal fa-search mr-1"></i> Wafer verfolgen</a>
                            <a href="javascript:;" @click="waferEdit = true"
                               class="bg-[#0085CA] text-xs px-3 py-1 uppercase hover:bg-[#0085CA]/80 rounded-sm text-white"><i
                                    class="fal fa-pencil mr-1"></i> Wafer bearbeiten</a>
                            @if(!$wafer->wafer->reworked && !$wafer->wafer->is_rework)
                                <a href="javascript:;"
                                   onclick="confirm('Willst du diesen Wafer wirklich als Nacharbeit markieren?') || event.stopImmediatePropagation()"
                                   wire:click="rework({{ $wafer->id }})"
                                   class="bg-orange-500 text-xs px-3 py-1 uppercase hover:bg-orange-500/80 rounded-sm text-white"><i
                                        class="fal fa-ban mr-1"></i> Nacharbeit</a>
                            @endif
                            <a href="javascript:;"
                               onclick="confirm('Willst du diesen Wafer wirklich löschen?') || event.stopImmediatePropagation()"
                               wire:click="removeEntry({{ $wafer->id }})"
                               class="bg-red-500 text-xs px-3 py-1 uppercase hover:bg-red-500/80 rounded-sm text-white"><i
                                    class="fal fa-trash mr-1"></i> Wafer löschen</a>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col justify-center items-center p-10">
                        <span class="text-lg font-bold text-red-500">Keine Wafer gefunden!</span>
                        <span
                            class="text-sm text-gray-500">Es wurden keine Wafer in diesem Arbeitsschritt gefunden.</span>
                    </div>
                @endforelse
            </div>
            <div class="flex flex-col justify-center items-center p-10 animate-pulse text-center"
                 wire:loading.delay.longer wire:target="search">
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
