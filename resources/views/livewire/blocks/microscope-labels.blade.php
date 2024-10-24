<?php
    use App\Models\Data\Process;
    use App\Models\Data\Serial;
    use Barryvdh\DomPDF\Facade\Pdf;
    use Milon\Barcode;

    new class extends \Livewire\Volt\Component {
        public $block = null;
        public $order = null;
        public $prevBlock;
        public $nextBlock;

        public $selectedWafers = [];
        public $startPos = 0;

        public function mount()
        {
            $blockInfo = BlockHelper::getPrevAndNextBlock($this->order, $this->block->id);
            $this->prevBlock = $blockInfo->prev;
            $this->nextBlock = $blockInfo->next;
        }

        public function getSelectedWafers() {
            $selectedWs = collect([])->pad(10, null);

            if($this->startPos + sizeof($this->selectedWafers) > 10) {
                $this->addError('print', 'Etikettenlimit für diese Seite überschritten!');
                return $selectedWs;
            }

            for($i = 0; $i < sizeof($this->selectedWafers); $i++) {
                $wafersForBox = Process::where('ar_box', $this->selectedWafers[$i])->
                whereRelation('rejection', 'reject', false)->where('block_id', 6)->lazy();

                $wafer = (object) [];
                $wafer->ar_box = $this->selectedWafers[$i];
                $wafer->date = $this->order->created_at;
                $wafer->article = $this->order->article;
                $wafer->format = $this->order->article_desc;
                $wafer->orders = collect();
                $wafer->boxes = collect();
                $wafer->count = 0;
                $wafer->lots = collect();

                foreach($wafersForBox as $waferForBox) {
                    $wafer->lots = collect(array_merge($wafer->lots->unique()->toArray(), Process::select('lot')->where('order_id', $waferForBox->order_id)->where('wafer_id', $waferForBox->wafer_id)->where('block_id', 2)->groupBy('lot')->pluck('lot')->toArray()));
                    $wafer->orders = collect(array_merge($wafer->orders->unique()->toArray(), [$waferForBox->order_id]));
                    $wafer->boxes = collect(array_merge($wafer->boxes->toArray(), Process::select('box')->where('order_id', $waferForBox->order_id)->where('wafer_id', $waferForBox->wafer_id)->where('block_id', 6)->groupBy('box')->pluck('box')->toArray()))->unique();
                    $wafer->count += 1;
                }

                $wafer->lots = $wafer->lots->unique();
                $wafer->orders = $wafer->orders->unique();

                $selectedWs->put($i + $this->startPos, $wafer);
            }

            return $selectedWs;
        }

        public function clearTemp() {
            foreach(glob('tmp/*.*') as $v){
                unlink($v);
            }
        }

        public function print() {
            $wafers = $this->getSelectedWafers();

            if(!empty($wafers)) {
                $startPos = $this->startPos;
                $pdf = Pdf::loadView('content.print.microscope-labels', compact('wafers', 'startPos'));
                $filename = "tmp/{$this->order->id}-" . rand() . ".pdf";
                $pdf->save($filename);
                $this->dispatch('printPdf', file: asset($filename));
            } else {
                $this->addError('print', "Es wurden keine Daten ausgewählt!");
            }
        }

        public function with()
        {
            $wafers = Process::select('ar_box')->where('order_id', $this->order->id)->where('block_id', 6)->where('reworked', false)->whereNotNull('ar_box')->groupBy('ar_box')->get();

            $selectedWs = collect([]);
            if(!empty($this->selectedWafers))
                $selectedWs = $this->getSelectedWafers();

            return compact(['wafers', 'selectedWs']);
        }
    }
?>

<div class="flex flex-col bg-white w-full h-full z-[9] border-l border-gray-200"
     x-data="{ selectedWafers: @entangle('selectedWafers').live }">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2"><i class="far fa-tag"></i></span>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="flex divide-x divide-gray-200 w-full h-full">
        <div class="grid grid-cols-3 gap-2 grow overflow-y-auto pb-4 z-[7] p-5">
            @foreach($wafers as $wafer)
                <label
                    class="flex gap-2 w-full h-20 items-center justify-center font-semibold border border-gray-200 rounded-sm shadow-md px-3 py-3 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="rounded-sm border-gray-300 text-[#0085CA] focus:ring-[#0085CA]"
                           value="{{ $wafer->ar_box }}" x-model="selectedWafers"/>
                    AR Box ID: {{ $wafer->ar_box }}
                </label>
            @endforeach
        </div>
        <div class="flex flex-col relative min-w-xl max-w-xl shrink-0 h-full w-full p-4 overflow-x-visible z-[8]"
             x-show="selectedWafers.length > 0">
            <div class="absolute w-full h-full bg-white bg-opacity-50 z-[9] justify-center items-center" wire:loading.flex>
                <span class="text-2xl font-extrabold text-[#0085CA]">Etiketten werden geladen...</span>
            </div>
            <div class="flex flex-col justify-between items-center z-[8]">
                <h1 class="text-lg font-semibold"><i class="fal fa-eye"></i> Vorschau</h1>
                <span class="text-xs">Die Vorschau entspricht nicht zu 100% der ausgedruckten Version</span>
                @if(!$errors->has('print'))
                    <a href="javascript:" wire:click="print"
                       class="bg-[#0085CA] rounded-sm px-2 py-1 uppercase mt-2 hover:bg-[#0085CA]/80 text-white text-sm w-max font-semibold">Etiketten
                        Drucken</a>
                @endif
                @error('print')
                <span class="text-xs font-semibold text-red-500 mt-2">{{ $message }}</span>
                @enderror
            </div>
            <div
                class="grid grid-cols-2 grid-rows-5 gap-[2px] bg-gray-200 rounded-sm mt-4 p-[4px] mb-16 shrink-0 h-[574px] w-[508px]">
                @foreach($selectedWs as $key => $selectedW)
                    @if($selectedW != null)
                        <div wire:click="$set('startPos', {{ $loop->index }})"
                             class="bg-white border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer hover:scale-150 hover:z-[8]">
                            <img class="absolute top-0 right-0" src="{{ asset('img/logo_rgb.png') }}" height="50"
                                 width="50"/>
                            <span class="absolute top-[10px] left-2 text-[7px] flex items-center">Life Technologies Holdings Pte. Ltd.</span>
                            <span
                                class="absolute top-[30px] left-2 text-[7px] flex gap-4 items-center">Artikelnummer <span>{{ $selectedW->article }}</span></span>
                            <span
                                class="absolute top-[39px] left-2 text-[7px] flex gap-4 items-center">PAS Format <span>{{ $selectedW->format }}</span></span>
                            <span
                                class="absolute top-[50px] right-2 text-[7px] flex gap-3 items-center">Datum <span>{{ $selectedW->date->format('m/d/y') }}</span></span>
                            <span
                                class="absolute top-[55px] left-2 text-[7px] flex gap-4 items-center">AR Box ID <span>{{ $selectedW->ar_box }}</span></span>
                            <span
                                class="absolute top-[65px] left-2 text-[7px] flex gap-4 items-center">{!! DNS1D::getBarcodeHTML($selectedW->ar_box, 'C128', 1, 8) !!}</span>
                            <span
                                class="absolute top-[78px] left-2 text-[6px] flex items-center gap-2">Chrom Chargen <span
                                    class="text-[6px]">{{ $selectedW->lots->join(', ') }}</span></span>
                            <span
                                class="absolute top-[90px] right-2 text-[7px] flex gap-1 items-center">Menge <span>{{ $selectedW->count }}</span></span>
                            <span
                                class="absolute top-[87px] left-2 text-[7px] flex gap-1 items-center">Box ID Chrom <span>{{ $selectedW->boxes->join(', ') }}</span></span>
                            <span class="absolute top-[96px] left-2 text-[7px] flex items-center gap-2">Auftragsnummern <span
                                    class="text-[6px]">{{ $selectedW->orders->join(', ') }}</span></span>
                        </div>
                    @elseif($loop->index < $this->startPos)
                        <div wire:click="$set('startPos', {{ $loop->index }})"
                             class="border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer flex flex-col items-center justify-center">
                            <span class="text-sm">Wird übersprungen</span>
                            <span class="text-xs text-gray-500">Klicke hier um die Etikette zu positionieren</span>
                        </div>
                    @else
                        <div wire:click="$set('startPos', {{ $loop->index }})"
                             class="bg-gray-100 border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer flex flex-col items-center justify-center">
                            <span class="text-xs text-gray-500">Klicke hier um die Etikette zu positionieren</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    @script
    <script>
        window.addEventListener('printPdf', function (filename) {
            var iframe = this._printIframe;
            if (!this._printIframe) {
                iframe = this._printIframe = document.createElement('iframe');
                document.body.appendChild(iframe);

                iframe.style.display = 'none';
                iframe.onload = function () {
                    setTimeout(function () {
                        iframe.focus();
                        iframe.contentWindow.print();
                        setTimeout(function () { @this.clearTemp() }, 100);
                    }, 1);
                };
            }

            iframe.src = filename.detail.file;
        })
    </script>
    @endscript
</div>
