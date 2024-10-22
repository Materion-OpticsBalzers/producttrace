<?php
    use Livewire\Attributes\Layout;
    use App\Models\Data\Coa;
    use App\Models\Data\Order;
    use App\Models\Data\Serial;
    use App\Models\Data\SerialList;
    use App\Models\User;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\File;
    use Illuminate\Support\Facades\Storage;
    use Livewire\Component;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Writer\Xls;

    new #[Layout('layouts.app')] class extends \Livewire\Volt\Component {
        public $search = '';
        public $searchAb = '';
        public $showSet = false;

        public function setOrder($orders, $po, $pos) {
            if($po == '') {
                $this->addError('po', 'Auftrag darf nicht leer sein!');
                return false;
            }

            if($pos == '') {
                $this->addError('pos', 'Position darf nicht leer sein!');
                return false;
            }

            if(empty($orders)) {
                $this->addError('po', 'Es muss mindestens ein auftrag ausgewählt werden!');
                return false;
            }

            $poSearch = DB::connection('oracle')->select("SELECT DOKNR, KUNDENDOKNR FROM PROD_ERP_001.DOK WHERE DOKNR = '{$po}'");

            if(empty($poSearch)) {
                $this->addError('po', 'Diese AB existiert nicht im ERP!');
                return false;
            }

            $orders = Order::find($orders);

            $initPos = $pos;
            foreach($orders as $order) {
                $posSearch = DB::connection('oracle')->select("SELECT DOKNR, POSNREXT, ARTNR FROM PROD_ERP_001.DOKPOS WHERE DOKNR = '{$po}' AND POSNREXT = {$pos} AND ROWNUM = 1");

                if(empty($posSearch)) {
                    $this->addError('pos', "Die Position {$pos} wurde in der AB ({$po}) nicht gefunden!");
                    return false;
                }

                if($posSearch[0]->artnr != $order->article) {
                    $this->addError('pos', "Der Artikel ({$posSearch[0]->artnr}) auf der AB Position {$pos} stimmt nicht mit dem Artikel ({$order->article}) im Auftrag ({$order->id}) überein!");
                    return false;
                }

                $poExists = Order::where('po', $po)->where('po_pos', $pos)->first();

                if($poExists != null) {
                    $this->addError('pos', "Die Postion {$pos} wurde schon einem anderen Auftrag ({$order->id}) zugewiesen!");
                    return false;
                }

                $pos += 10;
            }

            $pos = $initPos;
            foreach($orders as $order) {
                if($order->po == '') {
                    $order->update([
                        'po' => $po,
                        'po_pos' => $pos,
                        'po_cust' => $poSearch[0]->kundendoknr
                    ]);
                    $pos += 10;
                }

                $coa = $order->coa;
                if($coa != null) {
                    \CoaHelper::generateCoa($order, true, User::find($coa->user_id));
                }
            }

            $delivery_date = DB::connection('oracle')->select("SELECT BESTELLDATUM_C as datum FROM PROD_ERP_001.DOK
            LEFT JOIN PROD_ERP_001.CUST0011 ON CUST0011.DOKNR = DOK.DOKNR
            WHERE DOK.DOKNR = '{$po}'");

            if(!empty($delivery_date)) {
                $delivery_date = $delivery_date[0];
            }

            $sl = SerialList::updateOrCreate([
                'id' => $po
            ], [
                'id' => $po,
                'article' => $orders->first()->article,
                'article_cust' => $orders->first()->article_cust,
                'format' => $orders->first()->article_desc,
                'po_cust' => $poSearch[0]->kundendoknr,
                'delivery_date' => $delivery_date->datum
            ]);

            $this->generate($sl);

            session()->flash('success');
        }

        public function generate($po) {
            CoaHelper::generateSerialList($po);

            session()->flash('success');
        }

        public function unlink($order) {
            $order = Order::find($order);
            $sl = SerialList::find($order->po);

            $order->update([
                'po' => null,
                'po_pos' => null,
                'po_cust' => null
            ]);

            if($sl != null) {
                $this->generate($sl);
            }
        }

        public function with()
        {
            $orders = Order::orderBy('created_at', 'asc')->where('mapping_id', 4)->has('coa')->with('serials')->get();

            if(!$this->showSet)
                $orders = $orders->whereNull('po');

            if($this->search != '') {
                $orders = $orders->filter(function($value) {
                    return stristr($value->article, $this->search);
                });
            }

            if($this->searchAb != '') {
                $orders = $orders->filter(function($value) {
                    return stristr($value->po, $this->searchAb);
                });
            }

            $serialLists = SerialList::orderBy('created_at')->lazy();

            return compact(['orders', 'serialLists']);
        }
    }
?>

<div class="h-full flex" x-data="{ selected: [], showLists: false }">
    <div class="bg-white flex flex-col pt-4 max-w-sm min-w-sm w-full px-4 gap-2 shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[8]">
        <h1 class="font-semibold text-lg">Filter</h1>
        <input type="text" wire:model.live.debounce.500ms="search" class="rounded-sm border-0 focus:ring-[#0085CA] font-semibold bg-gray-200" placeholder="Artikel suchen..." />
        <input type="text" wire:model.live.debounce.500ms="searchAb" class="rounded-sm border-0 focus:ring-[#0085CA] font-semibold bg-gray-200" placeholder="AB suchen..." />
        <label class="flex items-center text-xs text-gray-500">
            <input type="checkbox" wire:model.live="showSet" class="mx-1 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" />
            Zugewiesene anzeigen
        </label>
    </div>
    <div class="flex flex-col w-full overflow-y-auto">
        <div class="flex shadow-md w-full divide-x divide-gray-200 z-[7] sticky top-0">
            <div @click="showLists = false" class="w-full bg-white p-4 flex flex-col rounded-sm hover:bg-gray-50 cursor-pointer" :class="!showLists ? 'text-[#0085CA]' : ''">
                <span class="uppercase font-semibold" ><i class="fal fa-memo-pad mr-1"></i> Produktionsaufträge</span>
                <span class="text-xs text-gray-400">Zeigt Produktionsaufträge an die zugewiesen werden können</span>
            </div>
            <div @click="showLists = true" class="w-full bg-white p-4 flex flex-col rounded-sm hover:bg-gray-50 cursor-pointer" :class="showLists ? 'text-[#0085CA]' : ''">
                <span class="uppercase font-semibold" ><i class="fal fa-list mr-1"></i>  Serialisationslisten</span>
                <span class="text-xs text-gray-400">Zeigt bereits erstellte Serialisationslisten an</span>
            </div>
        </div>
        <div class="flex h-full w-full">
            <div class="flex flex-col justify-between h-full w-full" x-show="!showLists">
                <div class="flex flex-col gap-2 w-full h-full">
                    <div class="bg-white flex flex-col divide-y divide-gray-200" wire:loading.remove.delay>
                        @forelse($orders as $order)
                            @if(isset($order->po))
                                <label class="items-center px-4 py-2 gap-2 hover:bg-gray-50 grid grid-cols-5">
                                    <span class="font-semibold items-center gap-2 flex">
                                        <input type="checkbox" value="{{ $order->id }}" class="mx-1 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" x-model="selected" />
                                        <a href="javascript:;" wire:click="unlink({{ $order->id }})" class="text-red-500 fa-fw"><i class="fal fa-unlink"></i></a>
                                        {{ $order->id }}
                                    </span>
                                    <span>Art: {{ $order->article }}</span>
                                    <span>{{ $order->article_cust }}</span>
                                    <span>Po: {{ $order->po }} - {{ $order->po_pos }}</span>
                                    <span class="text-gray-600">{{ $order->serials->first()->id ?? '' }} - {{ $order->serials->last()->id ?? '' }} ({{ $order->serials->count() }})</span>
                                </label>
                            @else
                                <label class="items-center gap-2 px-4 py-2 hover:bg-gray-50 grid grid-cols-5">
                                    <span class="font-semibold gap-2 flex items-center">
                                        <input type="checkbox" value="{{ $order->id }}" class="mx-1 rounded-sm text-[#0085CA] focus:ring-[#0085CA]" x-model="selected" />
                                        {{ $order->id }}
                                    </span>
                                    <span class="col-span-2">Art: {{ $order->article }}</span>
                                    <span>{{ $order->article_cust }}</span>
                                    <span class="text-gray-600">{{ $order->serials->first()->id ?? '' }} - {{ $order->serials->last()->id ?? '' }} ({{ $order->serials->count() }})</span>
                                </label>
                            @endif
                        @empty
                            <div class="text-center py-5">
                                <h1 class="font-bold text-lg text-red-500">Keine Aufträge gefunden bei denen ein CofA generiert wurde!</h1>
                                <span class="text-sm text-gray-500">Es wurden keine Aufträge gefunden die noch nicht zugewiesen sind, um zugewiesene Aufträge zu sehen wähle beim Filter "Zugewiesene anziegen" an.</span>
                            </div>
                        @endforelse
                            <div class="h-max sticky bottom-0 w-full bg-white shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[6]" x-data="{ po: '', pos: '' }" x-show="selected.length > 0" x-transition>
                                <div class="flex px-3 py-2">
                                    <a href="javascript:;" @click="selected = []" class="text-xs text-red-500"><i class="fal fa-times"></i> Auswahl aufheben</a>
                                </div>
                                <div class="flex flex-col gap-2 p-2">
                                    <input type="text" x-model="po" class="rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-gray-200 shadow-sm" placeholder="Auftragsbestätigung" />
                                    <input type="text" x-model="pos" class="rounded-sm border-0 h-8 focus:ring-[#0085CA] font-semibold bg-gray-200 shadow-sm" placeholder="Start Pos z.B (10)" />
                                    <button @click="$wire.setOrder(selected, po, pos)" class="bg-[#0085CA] font-semibold text-sm h-8 text-white hover:bg-[#0085CA]/80 h-full px-2 rounded-sm">Ausgewählte Aufträge zuweisen</button>
                                    @if(session()->has('success')) <span class="text-xs text-green-600 mt-0.5">Erfolgreich zugewiesen</span> @endif
                                    @error('po') <span class="text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                                    @error('pos') <span class="text-xs text-red-500 mt-0.5">{{ $message }}</span> @enderror
                                </div>
                            </div>
                    </div>
                    <div class="text-center bg-white py-5" wire:loading.delay>
                        <h1 class="font-bold text-lg"><i class="fal fa-spinner animate-spin mr-1"></i> Aufträge werden geladen...</h1>
                    </div>
                </div>

            </div>
            <div class="flex flex-col w-full gap-1 overflow-y-auto" x-show="showLists">
                <div class="bg-white flex flex-col divide-y divide-gray-200">
                    @forelse($serialLists as $list)
                        <a href="{{ route('serialise.list', ['po' => $list->id]) }}" class="items-center gap-2 px-4 py-2 hover:bg-gray-50 grid grid-cols-5">
                            <span class="font-semibold">
                                {{ $list->id }}
                            </span>
                            <span>Art: {{ $list->article }}</span>
                            <span>{{ $list->article_cust }}</span>
                            <span>{{ $list->format }}</span>
                        </a>
                    @empty
                        <div class="text-center py-10">
                            <h1 class="font-bold text-lg text-red-500">Keine Listen gefunden!</h1>
                            <span class="text-sm text-gray-500">Es wurden noch keine Serialisationslisten erstellt oder mit dem Filter wurde nichts gefunden.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
