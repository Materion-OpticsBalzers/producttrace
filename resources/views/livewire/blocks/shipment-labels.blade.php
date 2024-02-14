<?php
    use App\Models\Data\Process;
    use App\Models\Data\Serial;
    use Barryvdh\DomPDF\Facade\Pdf;

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

            $lot = Process::select('lot')->where('order_id', $this->order->id)->where('block_id', 8)->groupBy('lot')->first()->lot;
            $count = 0;
            foreach($this->selectedWafers as $selectedWafer) {
                $serials = Serial::where('order_id', $this->order->id)->with('wafer')->get();

                $wafer = (object) [];
                $wafer->article = $this->order->article;
                $wafer->format = $this->order->article_desc;
                $wafer->po = $this->order->po;
                $wafer->po_cust = $this->order->po_cust;
                $wafer->article_cust = $this->order->article_cust;
                $wafer->serials = $serials->filter(function($value, $key) use ($selectedWafer) {
                    return $key >= (($selectedWafer - 1) * 14) && $key < ($selectedWafer * 14);
                });

                $selectedWs->put($count + $this->startPos, $wafer);
                $count++;
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
                $pdf = Pdf::loadView('content.print.shipment-labels', compact('wafers', 'startPos'));
                $filename = "tmp/{$this->order->id}-" . rand() . ".pdf";
                $pdf->save($filename);
                $this->dispatch('printPdf', file: asset($filename));
            } else {
                $this->addError('print', "Es wurden keine Daten ausgeählt!");
            }
        }

        public function with()
        {
            $wafers = Serial::where('order_id', $this->order->id)->get();

            $blocks = round(($wafers->count() / 14));

            $selectedWs = collect([]);
            if(!empty($this->selectedWafers))
                $selectedWs = $this->getSelectedWafers();

            return compact(['blocks', 'selectedWs']);
        }
    }
?>

<div class="flex flex-col bg-white w-full h-full z-[9] border-l border-gray-200" x-data="{ selectedWafers: @entangle('selectedWafers').live }">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2"><i class="far fa-tag"></i></span>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="flex divide-x divide-gray-200 w-full h-full" >
        <div class="grid grid-cols-3 gap-2 grow overflow-y-auto pb-4 z-[7] p-5">
            @for($i = 0;$i < $blocks;$i ++)
                <label class="flex gap-2 w-full h-20 items-center justify-center font-semibold border border-gray-200 rounded-sm shadow-md px-3 py-3 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="rounded-sm border-gray-300 text-[#0085CA] focus:ring-[#0085CA]" value="{{ $i + 1 }}" x-model="selectedWafers"/>
                    Block: {{ $i + 1 }}
                </label>
            @endfor
        </div>
        <div class="flex flex-col relative min-w-xl max-w-xl shrink-0 h-full w-full p-4 overflow-x-visible z-[8]" x-show="selectedWafers.length > 0">
            <div class="absolute w-full h-full bg-white bg-opacity-50 z-[9] justify-center items-center" wire:loading.flex>
                <span class="text-2xl font-extrabold text-[#0085CA]">Etiketten werden geladen...</span>
            </div>
            <div class="flex flex-col justify-between items-center z-[8]" >
                <h1 class="text-lg font-semibold"><i class="fal fa-eye"></i> Vorschau</h1>
                <span class="text-xs">Die Vorschau entspricht nicht zu 100% der ausgedruckten Version</span>
                @if(!$errors->has('print'))
                    <a href="javascript:" wire:click="print" class="bg-[#0085CA] rounded-sm px-2 py-1 uppercase mt-2 hover:bg-[#0085CA]/80 text-white text-sm w-max font-semibold">Etiketten Drucken</a>
                @endif
                @error('print')
                <span class="text-xs font-semibold text-red-500 mt-2">{{ $message }}</span>
                @enderror
            </div>
            <div class="grid grid-cols-2 grid-rows-5 gap-[2px] bg-gray-200 rounded-sm mt-4 p-[4px] mb-16 shrink-0 h-[574px] w-[508px]">
                @foreach($selectedWs as $key => $selectedW)
                    @if($selectedW != null)
                        <div wire:click="$set('startPos', {{ $loop->index }})" class="bg-white border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer hover:scale-150 hover:z-[8]">
                            <img class="absolute top-0 right-0" src="{{ asset('img/logo_rgb.png') }}" height="50" width="50"/>
                            <span class="absolute top-[10px] left-2 text-[7px] flex items-center">Life Technologies Holdings Pte. Ltd.</span>
                            <span class="absolute top-[30px] left-2 text-[7px] flex gap-4 items-center">Lifetech P/O <span>{{ $selectedW->po_cust }}</span></span>
                            <span class="absolute top-[39px] left-2 text-[7px] flex gap-4 items-center">Lifetech P/N <span>{{ $selectedW->article_cust }}</span></span>
                            <span class="absolute top-[55px] left-2 text-[7px] flex gap-4 items-center">Balzers Ref# <span>{{ $selectedW->po }}</span></span>
                            <span class="absolute top-[64px] left-2 text-[7px] flex gap-4 items-center">Balzers P/N <span>{{ $selectedW->article }}</span></span>
                            <span class="absolute top-[83px] left-2 text-[7px] flex gap-4 items-center">Substrate ID: <span>{{ $selectedW->serials->first()->id . ' - ' . $selectedW->serials->last()->id }}</span></span>
                        </div>
                    @elseif($loop->index < $this->startPos)
                        <div wire:click="$set('startPos', {{ $loop->index }})" class="border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer flex flex-col items-center justify-center">
                            <span class="text-sm">Wird übersprungen</span>
                            <span class="text-xs text-gray-500">Klicke hier um die Etikette zu positionieren</span>
                        </div>
                    @else
                        <div wire:click="$set('startPos', {{ $loop->index }})" class="bg-gray-100 border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer flex flex-col items-center justify-center">
                            <span class="text-xs text-gray-500">Klicke hier um die Etikette zu positionieren</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <script>
        function printPdf(url) {
            var iframe = this._printIframe;
            if (!this._printIframe) {
                iframe = this._printIframe = document.createElement('iframe');
                document.body.appendChild(iframe);

                iframe.style.display = 'none';
                iframe.onload = function() {
                    setTimeout(function() {
                        iframe.focus();
                        iframe.contentWindow.print();
                        setTimeout(function () { @this.clearTemp() }, 100);
                    }, 1);
                };
            }

            iframe.src = url.file;
        }

        window.addEventListener('printPdf', function (filename) {
            printPdf(filename.detail)
        })
    </script>
</div>
