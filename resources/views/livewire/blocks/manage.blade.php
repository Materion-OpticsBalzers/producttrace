<?php
    use Livewire\Attributes\Layout;
    use App\Models\Data\Link;
    use App\Models\Data\Order;
    use App\Models\Data\Wafer;
    use App\Models\Data\Process;
    use App\Models\Generic\Block;
    use App\Models\Generic\Mapping;

    new class extends \Livewire\Volt\Component {
        public $block;
        public $order;

        public function mount() {

        }

        public function changeProduct($mappingId) {
            $this->order->update([
                'mapping_id' => $mappingId
            ]);

            $this->redirect(request()->header('Referer'), true);
        }

        public function removeAllData() {
            $data = Process::where('order_id', $this->order->id)->with('rejection');

            foreach($data->lazy() as $wafer) {
                if($wafer->rejection->reject) {
                    Wafer::find($wafer->wafer_id)->update([
                        'rejected' => false,
                        'rejection_reason' => null,
                        'rejection_position' => null,
                        'rejection_avo' => null,
                        'rejection_order' => null
                    ]);
                }
            }

            $data->delete();

            session()->flash('success');
        }

        public function updateOrder($customer, $supplier) {
            $this->order->update([
                'customer' => $customer,
                'supplier' => $supplier
            ]);

            $this->redirectIntended(request()->header('Referer'), true);
        }

        public function deleteOrder() {
            $this->order->delete();

            $this->redirect('/', true);
        }

        public function toggleWaferBoxCheck() {
            $this->order->toggleWaferCheck()->save();

            $this->redirectIntended(request()->header('Referer'), true);
        }

        public function with() {
            $block = Block::find($this->block->id);
            $products = Mapping::all();

            return compact(['block', 'products']);
        }
    }
?>

<div class="flex flex-col bg-gray-100 w-full h-full z-[9] border-l border-gray-200 overflow-y-auto">
    <div class="pl-8 pr-4 py-3 text-lg bg-white font-semibold flex border-b border-gray-200 items-center z-[9] sticky top-0">
        <i class="far fa-cog mr-2"></i>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="pl-8 pr-4 py-2 bg-white font-semibold flex gap-3 border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-calendar-circle-plus text-[#0085CA] mr-1"></i> Anmeldedatum</span>
            <span class="text-sm text-gray-500 mt-1">{{ $this->order->created_at != null ? $this->order->created_at->diffForHumans() : 'Unbekannt' }}</span>
        </div>
    </div>
    <div class="pl-8 pr-4 py-1 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-calendar-lines-pen text-[#0085CA] mr-1"></i> Letzte Änderung</span>
            <span class="text-sm text-gray-500 mt-1">{{ $this->order->updated_at != null ? $this->order->updated_at->diffForHumans() : 'Unbekannt' }}</span>
        </div>
    </div>
    <div class="pl-8 pr-4 py-2 mt-2 bg-white font-semibold flex border-b border-t border-gray-200 items-center z-[8]">
        <span class="font-semibold text-lg text-red-500"><i class="fal fa-exclamation-triangle mr-1"></i> Gefahrenzone</span>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-check text-orange-500 mr-1"></i> Waferprüfung für diesen Auftrag nach AR Box</span>
            <span class="text-xs text-gray-500">Wenn diese Funktion aktiviert ist, werden die Wafer in diesem Auftrag nur in der selben AR Box nach Fehlern überprüft!</span>
            <div class="flex mt-4 gap-2" x-data="{ active: {{ $this->order->wafer_check_ar }} }">
                <button onclick="confirm('Willst du diese Aktion wirklich ausführen?') || event.stopImmediatePropagation()" @click="$wire.toggleWaferBoxCheck()" :class="active ? '' : 'bg-gray-600 hover:bg-gray-600/80'"
                        class="bg-orange-500 hover:bg-orange-500/80 uppercase text-white rounded-sm text-sm px-3 py-1" x-text="active ? 'Deaktivieren' : 'Aktivieren'">
                </button>
            </div>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-sitemap text-orange-500 mr-1"></i> Auftrag bearbeiten</span>
            <div class="flex flex-col mt-4 gap-2" x-data="{ customer: '{{ $order->customer }}', supplier: '{{ $order->supplier }}' }">
                <div class="flex flex-col mb-2">
                    <label class="text-sm">Kundennummer</label>
                    <input type="text" x-model="customer" class="bg-gray-100 rounded-sm font-semibold border-0 text-sm focus:ring-[#0085CA]" placeholder="Kundennummer"/>
                </div>
                <div class="flex flex-col mb-2">
                    <label class="text-sm">Lieferant</label>
                    <input type="text" x-model="supplier" class="bg-gray-100 rounded-sm font-semibold border-0 text-sm focus:ring-[#0085CA]" placeholder="Lieferant"/>
                </div>
                <button onclick="confirm('Willst du den Auftrag wirklich ändern?') || event.stopImmediatePropagation()" @click="$wire.updateOrder(customer, supplier)" class="bg-orange-500 hover:bg-orange-500/80 w-max py-2 uppercase text-white rounded-sm text-sm px-3"><i class="fal fa-save mr-0.5"></i> Speichern</button>
            </div>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-sitemap text-orange-500 mr-1"></i> Mapping ändern (Auftragstyp)</span>
            <span class="text-xs text-gray-500">Ändert das zugewiesene Auftragstyp. Dies ändert die komplette Struktur und kann Daten durcheinanderbringen!</span>
            <div class="flex mt-4 gap-2" x-data="{ product: {{ $this->order->mapping_id }} }">
                <select x-model="product" class="bg-gray-100 rounded-sm font-semibold border-0 text-sm focus:ring-[#0085CA]">
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->product->name }} @if($product->id == $this->order->mapping_id) (Current) @endif</option>
                    @endforeach
                </select>
                <button onclick="confirm('Willst du das Mapping wirklich löschen?') || event.stopImmediatePropagation()" @click="$wire.changeProduct(product)" class="bg-orange-500 hover:bg-orange-500/80 uppercase text-white rounded-sm text-sm px-3"><i class="fal fa-pencil mr-0.5"></i> Ändern</button>
            </div>
        </div>
    </div>
    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-trash text-red-500 mr-1"></i> Alle Einträge löschen</span>
            <span class="text-xs text-gray-500">Hier können alle Einträge des Auftrags in allen Bearbeitungsschritten gelöscht werden. Diese aktion kann nicht rückgängig gemacht werden und ist deshalb mit Vorischt zu handhaben!</span>
            @if(session()->has('success')) <span class="text-xs text-green-600 mt-2">Daten erfolgreich gelöscht!</span> @endif
            <button onclick="confirm('Willst du wirklich alle Daten aus diesem Auftrag löschen?') || event.stopImmediatePropagation()" wire:click="removeAllData" class="bg-red-500 hover:bg-red-500/80 uppercase text-white rounded-sm text-sm px-3 py-1.5 w-fit mt-3"><i class="far fa-exclamation-triangle mr-1"></i> Alle Einträge Löschen</button>
        </div>
    </div>

    <div class="pl-8 pr-4 py-3 bg-white font-semibold flex border-b border-gray-200 items-center z-[8]">
        <div class="flex flex-col">
            <span class="font-semibold"><i class="fal fa-trash text-red-500 mr-1"></i> Auftrag löschen</span>
            <span class="text-xs text-gray-500">Löscht den kompletten Auftrag und die damit verbundenen Daten</span>
            <button onclick="confirm('Willst du diesen Auftrag wirklich löschen?') || event.stopImmediatePropagation()" wire:click="deleteOrder" class="bg-red-500 hover:bg-red-500/80 uppercase text-white rounded-sm text-sm px-3 py-1.5 w-fit mt-3"><i class="far fa-exclamation-triangle mr-1"></i> Auftrag löschen</button>
        </div>
    </div>
</div>
