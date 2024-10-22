<?php
    use Livewire\Attributes\Layout;
    use App\Models\Data\Order;
    use App\Models\Data\Serial;
    use Barryvdh\DomPDF\Facade\Pdf;
    use App\Models\Data\SerialList;
    use Livewire\Component;

    new #[Layout('layouts.app')] class extends \Livewire\Volt\Component {
        public $po;

        public function mount(SerialList $po) {
            $this->po = $po;
        }

        public function printOrders($orders) {
            $orders = collect($orders)->pad(5, null)->sort();
            $selectedWs = collect([])->pad(10, null);
            $count = 0;
            foreach($orders as $orderId) {
                $order = Order::find($orderId);
                $wafers = Serial::where('order_id', $orderId)->with('wafer')->get();
                $blocks = round($wafers->count() / 14);

                for($i = 1;$i <= $blocks; $i++) {
                    $wafer = (object) [];
                    $wafer->article = $order->article;
                    $wafer->format = $order->article_desc;
                    $wafer->po = $order->po;
                    $wafer->po_cust = $order->po_cust;
                    $wafer->article_cust = $order->article_cust;
                    $wafer->serials = $wafers->filter(function($value, $key) use ($i) {
                        return $key >= (($i - 1) * 14) && $key < ($i * 14);
                    });

                    $selectedWs->put($count, $wafer);
                    $count++;
                }
            }
            $wafers = $selectedWs;

            if(!empty($wafers)) {
                $startPos = 0;
                $pdf = Pdf::loadView('content.print.shipment-labels', compact('wafers', 'startPos'));
                $filename = "tmp/{$orderId}-" . rand() . ".pdf";
                $pdf->save($filename);
                $this->dispatch('printPdf', file: asset($filename));
            }
        }

        public function unlink($orderId) {
            $order = Order::find($orderId);

            $order->update([
                'po' => null,
                'po_pos' => null,
                'po_cust' => null
            ]);

            CoaHelper::generateSerialList($this->po);
        }

        public function clearTemp() {
            foreach(glob('tmp/*.*') as $v){
                unlink($v);
            }
        }

        public function with()
        {
            $orders = Order::where('po', $this->po->id)->with(['serials'])->orderBy('po_pos', 'asc')->get();

            return compact('orders');
        }
    }
?>

<div class="h-full flex">
    <div class="bg-white flex flex-col max-w-sm min-w-sm w-full px-4 pt-4 gap-2 shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[8]">
        <a href="{{ route('serialise') }}" wire:navigate class="text-red-500 mb-1"><i class="fal fa-arrow-left mr-1"></i>Zurück zur Serialisation</a>
        <h1 class="font-semibold text-lg">Optics Balzers Serialization Scheme</h1>
        <div class="flex flex-col divide-y divide-gray-200 bg-gray-100 rounded-sm px-2 py-1">
            <div class="flex justify-between w-full text-sm py-1">
                <span class="font-semibold">PO:</span>
                <span class="text-gray-600">{{ $po->po_cust }}</span>
            </div>
            <div class="flex justify-between w-full text-sm py-1">
                <span class="font-semibold">Date:</span>
                <span class="text-gray-600">{{ date('d.m.Y', strtotime($po->delivery_date)) }}</span>
            </div>
            <div class="flex justify-between text-sm w-full py-1">
                <span class="font-semibold">SO BZ:</span>
                <span class="text-gray-600 font-bold">{{ $po->id }}</span>
            </div>
            <div class="flex justify-between w-full text-sm py-1">
                <span class="font-semibold">PN BZ:</span>
                <span class="text-gray-600">{{ $po->article }}</span>
            </div>
            <div class="flex justify-between w-full text-sm py-1">
                <span class="font-semibold">PN:</span>
                <span class="text-gray-600">{{ $po->article_cust }}</span>
            </div>
            <div class="flex justify-between w-full text-sm py-1">
                <span class="font-semibold">Format:</span>
                <span class="text-gray-600">{{ $po->format }}</span>
            </div>
        </div>
        <div class="grow"></div>
    </div>
    <div class="flex flex-col w-full divide-y divide-gray-200 bg-white overflow-y-auto relative" x-data="{ selectedPos: [] }">
        <div class="w-full h-full justify-center items-center flex absolute" wire:loading.flex wire:target="unlink">
            <div class="bg-white bg-opacity-50 w-full h-full absolute"></div>
            <span class="text-2xl font-extrabold text-[#0085CA] z-[50]">Verlinkung wird aufgelöst und Serialliste neu generiert...</span>
        </div>
        <div class="px-4 py-2">
            <h1 class="font-bold text-lg sticky">Positionen</h1>
        </div>
        <div class="py-2 px-4" x-show="selectedPos.length > 0 && selectedPos.length <= 5" x-transition>
            <a href="javascript:" @click="$wire.printOrders(selectedPos)" class="bg-[#0085CA] text-white font-semibold rounded-sm hover:bg-[#0085CA]/80 text-sm px-2 py-1">Etiketten für ausgewählte Aufträge drucken</a>
        </div>
        <div class="flex flex-col divide-y divide-gray-200">
            @forelse($orders as $order)
                @php $missings = $order->missingSerials(); @endphp
                <div class="flex flex-col" x-data="{ open: false }">
                    <div class="flex items-center">
                        <input type="checkbox" class="ml-4 mr-2 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" x-model="selectedPos" value="{{ $order->id }}">
                        <a href="javascript:;" class="flex items-center w-full hover:bg-gray-50 px-4 py-2" @click="open = !open">
                            <i class="fal fa-chevron-right fa-fw mr-2 mt-1 shrink-0" x-show="!open"></i>
                            <i class="fal fa-chevron-down fa-fw mr-2 mt-1 shrink-0" x-show="open"></i>
                            <div class="flex gap-2 grow items-center">
                                <span class="font-semibold">{{ $order->po_pos }}</span>
                                <span class="text-gray-500">({{ $order->id }})</span>
                                <span class="h-max bg-gray-100 rounded-sm px-2 whitespace-nowrap">{{ sizeof($order->serials) - $missings->count() }} / {{ sizeof($order->serials) }}</span>
                                <span class="h-max items-center bg-gray-100 rounded-sm px-2 whitespace-nowrap">{{ $order->serials->first()->id ?? '?' }} - {{ $order->serials->last()->id ?? '?' }}</span>
                                @if($missings->count() > 0)
                                    <span class="bg-red-500/20 rounded-sm px-2">{{ join(', ', $missings->pluck('id')->toArray()) }}</span>
                                @endif
                            </div>
                        </a>
                    </div>
                    <div class="flex flex-col pl-12 text-sm pb-1" x-show="open">
                        <div class="flex gap-1 mb-1">
                            <a href="{{ route('orders.show', ['order' => $order->id]) }}" wire:navigate class="bg-[#0085CA] rounded-sm px-1 py-0.5 text-white hover:bg-[#0085CA]/80 w-fit"><i class="fal fa-link"></i> Zu diesem Auftrag springen</a>
                            <a href="javascript:;" wire:click="unlink('{{ $order->id }}')" class="bg-red-500 rounded-sm px-1 py-0.5 text-white hover:bg-red-500/80 w-fit"><i class="fal fa-unlink"></i> Verlinkung löschen</a>
                        </div>
                        <span><b>Ordered Qty:</b> {{ sizeof($order->serials) }}</span>
                        <span><b>Delivered Qty:</b> {{ sizeof($order->serials) - $missings->count() }}</span>
                        <span><b>Missing Serials:</b> {{ join(', ', $missings->pluck('id')->toArray()) }}</span>
                    </div>
                </div>
            @empty
            @endforelse
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
