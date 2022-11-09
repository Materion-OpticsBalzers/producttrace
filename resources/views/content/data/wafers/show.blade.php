<x-app-layout>
    <div class="flex h-full">
        <div class="flex flex-col h-full bg-white left-0 max-w-sm min-w-sm w-full shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] z-[8]">
            <div class="flex flex-col px-4 border-b border-gray-300 pb-4">
                <h1 class="text-xl font-bold @if($wafer->rejected) line-through @endif">{{ $wafer->id }} @if($serial != null) <i class="far fa-hashtag ml-1"></i> ({{ $serial->id }}) @endif</h1>
                <span class="text-sm text-gray-500"><i class="fal fa-upload mr-1"></i> Importiert {{ $wafer->created_at->diffForHumans() }}</span>
                @if($wafer->reworked)
                    <a href="{{ route('wafer.show', ['wafer' => $wafer->id.'-r']) }}" class="text-sm text-[#0085CA]"><i class="fal fa-link mr-1"></i> {{ $wafer->id.'-r' }}</a>
                @elseif($wafer->is_rework)
                    <a href="{{ route('wafer.show', ['wafer' => str_replace('-r', '', $wafer->id)]) }}" class="text-sm text-[#0085CA]"><i class="fal fa-link mr-1"></i> {{ str_replace('-r', '', $wafer->id) }}</a>
                @endif
            </div>
            <div class="flex flex-col divide-y divide-gray-300 border-b border-gray-300">
                @if($infos->supplier)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm px-3 py-1">
                        <h1 class="font-bold">Lieferant</h1>
                        <span class="text-gray-600">{{ $infos->supplier }}</span>
                    </div>
                @endif
                @if($serial != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm px-3 py-1">
                        <h1 class="font-bold">Seriennummer</h1>
                        <span class="text-gray-600">{{ $serial->id }}</span>
                    </div>
                @endif
                @if($infos->cr != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm px-3 py-1">
                        <h1 class="font-bold">Chromcharge</h1>
                        <span class="text-gray-600">{{ $infos->cr->lot }} ({{ $infos->cr->machine }})</span>
                    </div>
                @endif
                @if($infos->ar != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm px-3 py-1">
                        <h1 class="font-bold">AR Charge</h1>
                        <span class="text-gray-600">{{ $infos->ar->lot }} ({{ $infos->ar->machine }})</span>
                    </div>
                @endif
                @if($infos->po != null && $serial != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm px-3 py-1">
                        <h1 class="font-bold">Auftragsbestätigung</h1>
                        <span class="text-gray-600">{{ $infos->po->po }} - {{$infos->po->po_pos}}</span>
                    </div>
                @endif
                @if($wafer->rejected)
                    <div class="flex flex-col bg-red-500/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Wafer ist Ausschuss</h1>
                        <span class="text-xs text-red-500">{{ $wafer->rejection_reason }} in {{ $wafer->rejection_order }} <i class="fal fa-arrow-right"></i> {{ $wafer->rejection_avo }} - {{ $wafer->rejection_position }}</span>
                    </div>
                @else
                    <div class="flex flex-col bg-green-600/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Wafer in Ordnung</h1>
                        <span class="text-xs text-green-600">Dieser Wafer ist in Ordnung</span>
                    </div>
                @endif
                @if($wafer->reworked)
                    <div class="flex flex-col bg-orange-500/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Als Nacharbeit markiert</h1>
                        <span class="text-xs text-orange-500">Wurde zu <b>{{ $wafer->id.'-r' }}</b></span>
                    </div>
                @elseif($wafer->is_rework)
                    <div class="flex flex-col bg-orange-500/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Nacharbeitswafer</h1>
                        <span class="text-xs text-orange-500">Nacharbeit von <b>{{ str_replace('-r', '', $wafer->id) }}</b></span>
                    </div>
                @endif
                <div class="flex flex-col bg-gray-100 rounded-sm border border-gray-200 px-3 py-0.5">
                    <h1 class="font-bold"><i class="fal fa-arrow-down mr-1"></i> Kommt vor in diesen Produkten</h1>
                </div>
                <div class="flex flex-col bg-gray-50 rounded-sm border border-gray-200 px-3 py-2">
                    <span class="text-xs text-gray-500">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                    <h1 class="font-semibold text-[#0085CA]">Wafer Marking</h1>
                    <span class="text-xs text-gray-600">Markiert im Auftrag <b>{{ $wafer->order_id }}</b></span>
                </div>
                @foreach($waferOrders as $order)
                    <a href="{{ route('orders.show', ['order' => $order->order->id]) }}" class="flex hover:bg-gray-100 flex-col bg-gray-50 rounded-sm border border-gray-200 px-3 py-2">
                        <span class="text-xs text-gray-500">{{ date('d.m.Y H:i', strtotime($order->order->created_at)) }}</span>
                        <h1 class="font-semibold text-[#0085CA]">{{ $order->order->mapping->product->name }}</h1>
                        <span class="text-xs text-gray-600">Verwendet in <b>{{ $order->order->id }}</b></span>
                        @if($order->order->id == $wafer->rejection_order)
                            <span class="text-xs text-red-500">Wurde hier bei <b>{{ $wafer->rejection_position }}</b> als <b>{{ $wafer->rejection_reason }}</b> markiert!</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
        <div class="w-full bg-white h-full z-[7] overflow-y-auto">
            <div class="flex flex-col divide-y divide-gray-200">
                <div class="flex flex-col px-4 py-2">
                    <h1 class="text-xl font-bold">Waferhistorie</h1>
                    <span class="text-xs text-gray-500">Aufträge können aufgeklappt werden um Einträge anzusehen</span>
                </div>
                <div class="flex flex-col">
                    <a href="javascript:;" class="flex hover:bg-gray-50 px-4 py-2 items-center">
                        <i class="fal fa-info-circle fa-fw mr-2"></i>
                        <div class="flex flex-col">
                            <h1 class="font-bold">{{ $wafer->order_id }}</h1>
                            <span class="text-xs text-gray-400">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                            <span class="text-sm text-gray-600">Wafer Marking ({{ $wafer->box }})</span>
                        </div>
                    </a>
                </div>
                @foreach($waferOrders as $order)
                    <div class="flex flex-col" x-data="{ open: false }">
                        <a href="javascript:;" @click="open = !open" class="flex hover:bg-gray-50 px-4 py-2 items-center">
                            <i class="fal fa-fw fa-chevron-right mr-2" x-show="!open"></i>
                            <i class="fal fa-fw fa-chevron-down mr-2" x-show="open"></i>
                            <div class="flex flex-col">
                                <h1 class="font-bold">{{ $order->order->id }}</h1>
                                <span class="text-sm text-gray-600">{{ $order->order->mapping->product->name }}</span>
                                <div class="flex gap-2 mt-1 text-xs">
                                    @if($order->order->po != '')
                                        <span class="bg-gray-100 rounded-sm px-1 py-1"><b>Auftragsbestätigung:</b> {{$order->order->po}} - {{$order->order->po_pos}}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                        <div class="flex flex-col pb-2 gap-1 pl-10 mr-2" x-show="open">
                            <?php $lastBlock = ""; ?>
                            @foreach($waferData->where('order_id', $order->order_id)->sortBy('block.avo') as $data)
                                @if($lastBlock != $data->block_id)
                                    <div class="flex py-1 bg-gray-200 rounded-sm px-2 mt-1">
                                        <span class="text-sm font-bold">
                                            @if($data->block->icon != '') <i class="fal {{ $data->block->icon }}"></i> @else {{ $data->block->avo }} @endif - {{ $data->block->name }}
                                        </span>
                                    </div>
                                    <?php $lastBlock = $data->block_id; ?>
                                @endif
                                <div class="flex px-2 py-1 @if($data->rejection->reject ?? false) bg-red-500/10 @else bg-gray-100 @endif rounded-sm w-full items-center">
                                    @if($data->rejection->reject ?? false)
                                        <i class="far fa-fw text-red-500 fa-ban mr-2"></i>
                                    @else
                                        <i class="far fa-fw text-green-600 fa-check mr-2"></i>
                                    @endif
                                    <div class="flex flex-col w-full">
                                        <span class="text-xs text-gray-500">{{ date('d.m.Y H:i', strtotime($data->created_at)) }}</span>
                                        <span class="text-sm @if($data->wafer_id == $wafer->id) font-bold @else italic @endif @if($data->wafer->rejected) line-through @endif">
                                            {{ $data->wafer_id }}
                                            @if($data->wafer_id == $wafer->id)
                                                <i class="fas fa-chevron-left ml-2 animate-pulse text-[#0085CA]"></i>
                                            @endif
                                        </span>
                                        @if($data->rejection->reject ?? false)
                                            <span class="text-xs text-red-500">{{ $data->rejection->name }}</span>
                                        @endif
                                        @if($data->reworked)
                                            <span class="text-xs text-orange-500">Wurde hier als Nacharbeit markiert</span>
                                        @endif
                                        <div class="flex gap-2 items-center mt-1 mb-1 text-xs">
                                            <span class="bg-gray-200 rounded-sm px-1 py-0.5"><b>Operator:</b> {{ $data->operator }}</span>
                                            <span class="bg-gray-200 rounded-sm px-1 py-0.5"><b>Box:</b> {{ $data->box }}</span>
                                            @if($data->lot != '') <span class="bg-gray-200 rounded-sm px-1 py-0.5"><b>Charge:</b> {{ $data->lot }}</span> @endif
                                            @if($data->machine != '') <span class="bg-gray-200 rounded-sm px-1 py-0.5"><b>Anlage:</b> {{ $data->machine }}</span> @endif
                                            @if($data->position != '') <span class="bg-gray-200 rounded-sm px-1 py-0.5"><b>Position:</b> {{ $data->position }}</span> @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
