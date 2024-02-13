<?php
    use App\Models\Data\Wafer;
    use App\Models\Data\Process;
    use App\Models\Data\Serial;
    use Illuminate\Support\Facades\Storage;

    new class extends \Livewire\Volt\Component {
        public $block;
        public $order;

        public $search = '';

        public function importWafers($box) {
            if($box == '') {
                $this->addError('import', 'Die Box ID darf nicht leer sein!');
                return false;
            }

            $file = collect(Storage::drive('s')->files('090 Produktion/10 Linie 1/20 Produktionsanlagen/200 PhotonEnergy/LaserMarkingDataManagementOutput'))->filter(function($value) use ($box) {
                return str_starts_with(basename($value), $box) && str_ends_with(basename($value), 'DMC.txt');
            })->first();

            if($file != null) {
                $values = explode(';', explode("\n", Storage::disk('s')->get($file))[1]);
                $values = array_splice($values, 1);
                $values = array_filter($values, function ($value) {
                    return $value != '';
                });
                $wafers = array_unique($values);

                foreach($wafers as $wafer) {
                    $cWafer = Wafer::firstOrCreate(
                        ['id' => $wafer],
                        ['order_id' => $this->order->id, 'box' => $box]
                    );
                }

                session()->flash('success');
            } else {
                $this->addError('import', 'Es konnte keine passende Datei gefunden werden!');
            }
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

        public function with()
        {
            $wafers = Wafer::where('order_id', $this->order->id)->with('order')->orderBy('id')->lazy();

            if($this->search != '') {
                $wafers = $wafers->filter(function ($value, $key) {
                    return stristr($value->id, $this->search);
                });
            }

            return compact('wafers');
        }
    }
?>

<div class="flex flex-col bg-white w-full h-full z-[9] border-l border-gray-200">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <i class="far fa-upload mr-2"></i>
        <span class="grow">{{ $block->name }}</span>
        @can('is-admin')
            @if($wafers->count() > 0)
                <a href="javascript:;" onclick="confirm('Bist du sicher das du alle Einträge löschen willst?') || event.stopImmediatePropagation()" wire:click="clear()" class="bg-red-500 hover:bg-red-500/80 rounded-sm px-2 py-1 text-sm text-white uppercase font-semibold mt-1">Alle Positionen Löschen</a>
            @endif
        @endcan
    </div>
    <div class="px-8 py-3 bg-white border-b border-gray-200 shadow-sm z-[8] flex flex-col">
        <div class="flex gap-2 items-center" x-data="{ box: '' }">
            <i class="far fa-sync animate-spin" wire:loading wire:target="importWafers"></i>
            <input type="text" x-model="box" class="bg-gray-200 rounded-sm border-0 focus:ring-[#0085CA] font-semibold text-xs" wire:loading.remove wire:target="importWafers" placeholder="Box ID" autofocus />
            <a href="javascript:;" @click="$wire.importWafers(box)" wire:loading.remove wire:target="importWafers" class="bg-[#0085CA] px-2 py-1 text-sm text-white hover:bg-[#0085CA]/80 rounded-sm uppercase"><i class="fal fa-upload mr-1"></i> Importieren</a>
            <span class="text-xs text-gray-500"><i class="far text-[#0085CA] fa-exclamation-triangle mr-0.5"></i> Die Wafer werden automatisch gesucht, falls der Import nicht funktioniert konnte das Log-File für diesen Auftrag nicht gefunden werden!
            Wenn ein Wafer importiert wird der schon vorhanden ist wird dieser ignoriert.</span>
        </div>
        @if(session()->has('success')) <span class="text-xs mt-2 font-semibold text-green-600">Wafer wurden erfolgreich importiert!</span> @endif
        @error('import') <span class="text-xs mt-2 font-semibold text-red-500">{{ $message }}</span> @endif
    </div>
    <div class="h-full bg-gray-100 flex z-[7]">
        <div class="w-full px-4 py-3 overflow-y-auto flex flex-col pb-36">
            <h1 class="text-base font-bold">Importierte Wafer ({{ $wafers->count() }})</h1>
            <input type="text" wire:model.blur="search" onfocus="this.setSelectionRange(0, this.value.length)" class="bg-white rounded-sm mt-2 mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]" placeholder="Wafer durchsuchen..." />
            <div class="flex flex-col gap-1 mt-2" wire:loading.remove.delay.longer wire:target="search">
                <div class="px-2 py-1 rounded-sm grid grid-cols-4 items-center justify-between bg-gray-200 shadow-sm mb-1">
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Box ID</span>
                    <span class="text-sm font-bold"><i class="fal fa-delivery mr-1"></i> Glas Lieferant</span>
                    <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                </div>
                @forelse($wafers as $wafer)
                    <div class="px-2 py-1 bg-white border border-green-600/50 flex rounded-sm hover:bg-gray-50 items-center">
                        <div class="flex flex-col grow">
                            <div class="flex grid grid-cols-4 items-center">
                                <span class="text-sm font-semibold">{{ $wafer->id }}</span>
                                <span class="text-sm">{{ $wafer->box }}</span>
                                <span class="text-sm">{{ $wafer->order->supplier }}</span>
                                <span class="text-xs text-gray-500 truncate">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                            </div>
                            <span class="text-xs text-gray-400 italic">Wafer erfolgreich importiert</span>
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
