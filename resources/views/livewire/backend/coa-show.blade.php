<?php
    use Livewire\Attributes\Layout;
    use App\Models\Data\Order;
    use App\Models\Data\Coa;
    use App\Models\Data\SerialList;

    new #[Layout('layouts.app')] class extends \Livewire\Volt\Component {
        public $order;
        public $coa;

        public $adminMode = false;

        public function mount(Order $order)
        {
            $this->order = $order;
            $this->coa = $order->coa;
        }

        public function toggleAdmin() {
            $this->adminMode = !$this->adminMode;
        }

        public function generateCoa() {
            if(\CoaHelper::generateCoa($this->order)) {
                session()->flash('success');
            }
        }

        public function approveOrder($hasPo = false) {
            $this->coa = Coa::updateOrCreate(['order_id' => $this->order->id], [
                'user_id' => auth()->id(),
                'serialized' => $hasPo,
            ]);

            session()->flash('approved');
        }

        public function undoApprove() {
            $this->coa->delete();
            $this->coa = null;

            if($this->order->po) {
                $po = SerialList::find($this->order->po);

                $this->order->update([
                    'po' => null,
                    'po_cust' => null,
                    'po_pos' => null
                ]);

                CoaHelper::generateSerialList($po);
            }
        }

        public function with()
        {
            $this->resetErrorBag();

            $data = \CoaHelper::loadCoaData($this->order);


            $hasErrors = false;
            foreach($data->serials as $serial) {
                $serial->errorIn = collect([]);
                foreach($serial->wafer->processes as $key => $process) {
                    if(!$process && !$serial->wafer->rejected) {
                        $serial->hasError = true;
                        $hasErrors = true;
                        $serial->errorIn->add(\App\Models\Generic\Block::find($key));
                    }
                }
            }

            foreach($data->chrom_lots as $chrom_lot) {
                if($chrom_lot->cd_ol->count() < 2 || $chrom_lot->cd_ur->count() < 2) {
                    $this->addError('chrome', "Mindestens eine Chrom Charge hat nicht genug CD Werte");
                }
            }

            if(sizeof($data->found_files) < 6)
                $this->addError('files', "Es konnten nicht alle Kurvendateien gefunden werden");

            if(empty($data->ar_data)) {
                $this->addError('ar_data', "Es konnte keine AR Daten für diesen Auftrag und die Charge im CAQ gefunden werden!");
            }

            if($hasErrors) {
                $this->addError('serials', "Ein oder mehr Serials haben Fehler!");
            }

            if($data->serials->count() < 28)
                $this->addError('serials', 'Es wurden noch nicht alle Serials klassifiziert, vermutlich wurden diese in der FF vergessen einzuscannen');

            return ['serials' => $data->serials, 'packaging_date' => $data->packaging_date, 'found_files' => $data->found_files, 'ar_data' => $data->ar_data, 'ar_info' => $data->ar_info, 'chrom_lots' => $data->chrom_lots];
        }
    }
?>

<div class="h-full w-full overflow-y-auto relative">
    <div class="absolute justify-center items-center flex w-full h-full z-[50]" wire:loading.flex>
        <div class="absolute w-full h-full bg-white/50 z-[50]"></div>
        <span class="text-2xl text-center text-[#0085CA] font-extrabold z-[51]"><i class="fal fa-spinner fa-spin"></i> Daten werden geladen</span>
    </div>
    <div class="h-full flex flex-col max-w-6xl min-w-6xl mx-auto pt-4 pb-4 mb-4 w-full">
        <div class="flex justify-between">
            <h1 class="text-xl font-bold">CofA für {{ $order->id }}</h1>
            <a href="javascript:;" wire:click="toggleAdmin"><i class="fal fa-shield"></i></a>
        </div>
        @if($coa)
            <span class="text-green-500 bg-green-100 text-xs font-semibold rounded-sm px-2 py-1">Dieses CofA ist freigegeben</span>
        @endif
        @if($errors->getMessageBag()->count() == 0 || $adminMode)
            @if($adminMode)
                <div class="flex bg-red-500/50 text-sm rounded-md px-2 py-1">Admin-Modus</div>
            @endif
            <div class="flex my-2 gap-1">
                @if(!$coa)
                    <a href="javascript:" wire:click="generateCoa" class="bg-[#0085CA] rounded-md px-2 py-1 hover:bg-[#0085CA]/80 text-white font-semibold uppercase">CofA generieren</a>
                    <a href="javascript:" wire:click="approveOrder({{ (bool)$order->po }})" class="bg-green-600 rounded-md px-2 py-1 hover:bg-green-600/80 text-white font-semibold uppercase">CofA freigeben</a>
                @else
                    <a href="javascript:" wire:click="undoApprove()" class="bg-red-500 rounded-md px-2 py-1 hover:bg-red-500/80 text-white font-semibold uppercase">Freigabe aufheben</a>
                @endif
            </div>
            @if(session('approved')) <span class="text-green-500 text-xs mb-2 px-2 py-1 bg-green-100 font-semibold rounded-sm">CofA wurde für Serialisierung freigegeben</span> @endif
        @else
            <a href="javascript:" class="bg-red-500/50 rounded-md px-2 py-1 my-2 hover:bg-red-500/80 font-semibold cursor-not-allowed"><i class="fal fa-exclamation-triangle mr-1"></i> Bitte zuerst Fehler beheben bevor das COFA generiert werden kann</a>
        @endif
        @if(session('success')) <span class="rounded-md px-2 py-1 bg-green-100 text-green-500 font-semibold text-xs mb-2">CofA wurde erfolgreich generiert</span> @endif
        <div class="bg-white rounded-md shadow-sm">
            <span class="font-semibold flex border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">Informationen</span>
            @if($serials->count() > 0)
                <div class="flex flex-col p-2">
                    @if(!$order->po) <span class="rounded-md px-2 py-1 bg-orange-100 text-orange-500 font-semibold text-xs mb-2">Dieser Auftrag wurde noch nicht serialisiert</span> @endif
                    <div class="grid grid-cols-2 text-sm bg-gray-100 rounded-md p-2">
                        <span><b>Customer P.O. No.:</b> {{ $order->po_cust }}</span>
                        <span><b>Packaging Date:</b> {{ $packaging_date ?? 'Noch nicht verpackt' }}</span>
                        <span><b>Life Tech Part No.:</b> {{ $order->article_cust }}</span>
                        <span><b>Optics Ref. No.:</b> {{ $order->po }}</span>
                        <span><b>Optics Part No.:</b> {{ $order->article }}</span>
                        <span><b>AR Lot.:</b> {{ $ar_info->lot }}</span>
                    </div>
                </div>
            @endif
        </div>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold flex border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">Positionen ({{ $serials->count() }})</span>
            <div class="flex flex-col p-2">
                @error('serials') <span class="rounded-md px-2 py-1 bg-red-100 text-red-500 font-semibold text-xs mb-2">{{ $message }}</span> @enderror

                <div class="grid grid-cols-8 divide-y bg-gray-100 p-2 rounded-md divide-gray-100 text-center">
                    <span class="py-0.5 text-xs font-semibold">Serial</span>
                    <span class="py-0.5 text-xs font-semibold">Position</span>
                    <span class="py-0.5 text-xs font-semibold">Wafer ID</span>
                    <span class="py-0.5 text-xs font-semibold">Rohglas</span>
                    <span class="py-0.5 text-xs font-semibold">Chrom</span>
                    <span class="py-0.5 text-xs font-semibold">Chrom Anlage</span>
                    <span class="py-0.5 text-xs font-semibold">Litho</span>
                    <span class="py-0.5 text-xs font-semibold">Leybold</span>
                    @forelse($serials as $serial)
                        <div class="grid grid-cols-8 col-span-8  @if($serial->hasError) bg-orange-500/50 font-semibold @endif @if($serial->wafer->rejected || $serial->rejected) bg-red-500/50 font-semibold @endif">
                            <span class="py-0.5 text-xs">{{ $serial->id }}</span>
                            <span class="py-0.5 text-xs">{{ $serial->wafer->rejected || $serial->rejected ? 'Missing' : substr($serial->wafer->processes[BlockHelper::BLOCK_ARC]->position ?? '?', 0, 1) }}</span>
                            <span class="py-0.5 text-xs">{{ str_replace('-r', '', $serial->wafer_id ?? $serial->wafer->id) }}</span>
                            <span class="py-0.5 text-xs">{{ $serial->wafer->order->supplier ?? 'Missing' }}</span>
                            <span class="py-0.5 text-xs">{{ $serial->wafer->processes[BlockHelper::BLOCK_CHROMIUM_COATING]->lot ?? 'Missing' }}</span>
                            <span class="py-0.5 text-xs">{{ $serial->wafer->processes[BlockHelper::BLOCK_CHROMIUM_COATING]->machine ?? 'Missing' }}</span>
                            <span class="py-0.5 text-xs">{{ $serial->wafer->processes[BlockHelper::BLOCK_LITHO]->machine ?? 'Missing' }}</span>
                            <span class="py-0.5 text-xs">{{ $serial->wafer->processes[BlockHelper::BLOCK_ARC]->machine ?? 'Missing' }}</span>
                            @if($serial->hasError)
                                <div class="col-span-8 px-4 font-semibold text-xs py-0.5 w-full flex">
                                    <span>Fehlt in: </span>
                                    @foreach($serial->errorIn as $e)

                                        {{ $e->name . ', ' }}
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    @empty
                    @endforelse
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold items-center flex justify-between border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">
                Mikroskop
            </span>
            <div class="flex flex-col p-2">
                @error('chrome') <span class="rounded-md px-2 py-1 bg-red-100 text-red-500 font-semibold text-xs mb-2">{{ $message }}</span> @enderror
                <div class="grid grid-cols-5 text-xs p-2 rounded-md bg-gray-100 text-center">
                    <span class="py-0.5 font-semibold">Specification</span>
                    <span class="py-0.5 font-semibold">Tolerance</span>
                    <span class="py-0.5 font-semibold">Right lower side</span>
                    <span class="py-0.5 font-semibold">Left uper side</span>
                    <span class="py-0.5 font-semibold row-span-2">Optics Balzers lot number</span>
                    <span class="py-0.5 font-semibold">[µm]</span>
                    <span class="py-0.5 font-semibold">[µm]</span>
                    <span class="py-0.5 font-semibold">[µm]</span>
                    <span class="py-0.5 font-semibold">[µm]</span>
                    @forelse($chrom_lots as $lot)
                        @php
                            $cd_ur_avg = number_format($lot->cd_ur->filter(function($value) {
                                return $value <> 0;
                            })->avg(), 2);
                            $cd_ol_avg = number_format($lot->cd_ol->filter(function($value) {
                                return $value <> 0;
                            })->avg(), 2);

                            $bg_class = '';

                            if($cd_ol_avg < 4 || $cd_ol_avg > 6)
                                $bg_class = 'bg-red-500/50 font-semibold';

                            if($cd_ur_avg < 4 || $cd_ur_avg > 6)
                                $bg_class = 'bg-red-500/50 font-semibold';
                        @endphp
                        <span class="py-0.5 {{ $bg_class }}">5</span>
                        <span class="py-0.5 {{ $bg_class }}">±1</span>
                        <span class="py-0.5 {{ $bg_class }}">{{ $cd_ur_avg }}</span>
                        <span class="py-0.5 {{ $bg_class }}">{{ $cd_ol_avg }}</span>
                        <span class="py-0.5 {{ $bg_class }}">{{ $lot->lot }}</span>

                        @if($lot->cd_ol->count() < 2 || $lot->cd_ur->count() < 2)
                            <span class="col-span-5 bg-red-500/50 font-semibold">Zu wenig CD Werte</span>
                        @endif
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold items-center flex justify-between border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">
                AR Daten
                <span class="text-xs"><i class="fal fa-database mr-1"></i> CAQ</span>
            </span>
            <div class="flex flex-col p-2">
                @error('ar_data') <span class="rounded-md px-2 py-1 bg-red-100 text-red-500 font-semibold text-xs mb-2">{{ $message }}</span> @enderror

                <div class="grid grid-cols-5 text-xs p-2 rounded-md bg-gray-100 text-center">
                    <span class="py-0.5 font-semibold">Wavelength</span>
                    <span class="py-0.5 font-semibold">Specification</span>
                    <span class="py-0.5 font-semibold">Result</span>
                    <span class="py-0.5 font-semibold">Specification</span>
                    <span class="py-0.5 font-semibold">Result</span>
                    <span class="py-0.5 font-semibold">nm</span>
                    <span class="py-0.5 font-semibold">R[%]</span>
                    <span class="py-0.5 font-semibold">R[%]</span>
                    <span class="py-0.5 font-semibold">T[%]</span>
                    <span class="py-0.5 font-semibold">T[%]</span>
                    <!--row 1 -->
                    @if(!empty($ar_data))
                        <span class="py-0.5">365</span>
                        <span class="py-0.5">≤ 8</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[0]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">≤ 2</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[3]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">405</span>
                        <span class="py-0.5">≤ 7</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[1]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">< 15</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[4]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">436</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">≥ 13</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[5]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">440</span>
                        <span class="py-0.5">15 - 40</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[2]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">488</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">60 - 80</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[6]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">530</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">75 - 90</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[7]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">570</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">80 - 90</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[8]->TWERTE))->get(1) }}</span>
                        <span class="py-0.5">670</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">n/a</span>
                        <span class="py-0.5">> 75</span>
                        <span class="py-0.5">{{ collect(explode(';', $ar_data[9]->TWERTE))->get(1) }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold items-center flex justify-between border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">
                Kurven
                <span class="text-xs"><i class="fal fa-files mr-1"></i> Dateien</span>
            </span>
            <div class="flex flex-col p-2">
                @error('files') <span class="rounded-md px-2 py-1 bg-red-100 text-red-500 font-semibold text-xs mb-2">{{ $message }}</span> @enderror
                <div class="flex flex-col p-2 bg-gray-100 rounded-md">
                    @forelse($found_files as $file)
                        <span class="text-sm">{{ $file->file }}</span>
                    @empty
                        <span class="text-xs">Es konnten keine Kurven gefunden werden</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
