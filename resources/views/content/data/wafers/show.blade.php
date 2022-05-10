<x-app-layout>
    <div class="flex h-full">
        <div class="flex flex-col h-full bg-white left-0 max-w-sm min-w-sm w-full shadow-[0px_0px_10px_0px_rgba(0,0,0,0.3)] pt-32 z-[8]">
            <div class="flex flex-col px-4 border-b border-gray-300 pb-4">
                <h1 class="text-xl font-bold">{{ $wafer->id }} @if($serial != null) - ({{ $serial->id }}) @endif</h1>
                <span class="text-sm text-gray-500"><i class="fal fa-upload mr-1"></i> Importiert {{ $wafer->created_at->diffForHumans() }}</span>
                @if($wafer->reworked)
                    <a href="{{ route('wafer.show', ['wafer' => $wafer->id.'-r']) }}" class="text-sm text-[#0085CA]"><i class="fal fa-link mr-1"></i> {{ $wafer->id.'-r' }}</a>
                @elseif($wafer->is_rework)
                    <a href="{{ route('wafer.show', ['wafer' => str_replace('-r', '', $wafer->id)]) }}" class="text-sm text-[#0085CA]"><i class="fal fa-link mr-1"></i> {{ str_replace('-r', '', $wafer->id) }}</a>
                @endif
            </div>
            <div class="flex flex-col divide-y divide-gray-300">
                @if($wafer->rejected)
                    <div class="flex flex-col bg-red-500/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Ausschuss</h1>
                        <span class="text-xs text-red-500">{{ $wafer->rejection_reason }} in {{ $wafer->rejection_order }} <i class="fal fa-arrow-right"></i> {{ $wafer->rejection_avo }} - {{ $wafer->rejection_position }}</span>
                    </div>
                @else
                    <div class="flex flex-col bg-green-600/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Wafer In Ordnung</h1>
                        <span class="text-xs text-green-600">Dieser Wafer ist in Ordnung</span>
                    </div>
                @endif
                @if($wafer->reworked)
                    <div class="flex flex-col bg-orange-500/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Nacharbeit</h1>
                        <span class="text-xs text-orange-500">Wurde zu <b>{{ $wafer->id.'-r' }}</b></span>
                    </div>
                @elseif($wafer->is_rework)
                    <div class="flex flex-col bg-orange-500/10 rounded-sm px-3 py-2">
                        <h1 class="font-semibold">Nacharbeitswafer</h1>
                        <span class="text-xs text-orange-500">Nacharbeit von <b>{{ str_replace('-r', '', $wafer->id) }}</b></span>
                    </div>
                @endif
                @if($serial != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm border border-gray-200 px-3 py-1">
                        <h1 class="font-bold">Seriennummer</h1>
                        <span class="text-gray-600">{{ $serial->id }}</span>
                    </div>
                @endif
                @if($infos->crlot != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm border border-gray-200 px-3 py-1">
                        <h1 class="font-bold">Chromcharge</h1>
                        <span class="text-gray-600">{{ $infos->crlot }}</span>
                    </div>
                @endif
                @if($infos->arlot != null)
                    <div class="flex justify-between items-center bg-gray-50 rounded-sm border border-gray-200 px-3 py-1">
                        <h1 class="font-bold">AR Charge</h1>
                        <span class="text-gray-600">{{ $infos->arlot }}</span>
                    </div>
                @endif
                <div class="flex flex-col bg-gray-100 rounded-sm border border-gray-200 px-3 py-0.5">
                    <h1 class="font-bold">Kommt vor in diesen Aufträgen <i class="fal fa-arrow-down ml-1"></i></h1>
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
                <h1 class="pt-32 px-4 py-2 text-lg font-bold">Waferhistorie</h1>
                <div class="flex flex-col">
                    <a href="javascript:;" class="flex hover:bg-gray-50 px-4 py-2 items-center">
                        <i class="fal fa-info-circle fa-fw mr-2"></i>
                        <div class="flex flex-col">
                            <h1 class="font-semibold">{{ $wafer->order_id }}</h1>
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
                                <h1 class="font-semibold">{{ $order->order->id }}</h1>
                                <span class="text-sm text-gray-600">{{ $order->order->mapping->product->name }}</span>
                            </div>
                        </a>
                        <div class="flex flex-col pb-2 gap-1 pl-10 mr-2" x-show="open">
                            <?php $lastBlock = ""; ?>
                            @foreach($waferData->where('order_id', $order->order_id)->sortBy('block.avo') as $data)
                                @if($lastBlock != $data->block_id)
                                    <div class="flex py-1">
                                        <span class="text-sm font-semibold">
                                            @if($data->block->icon != '') <i class="fal {{ $data->block->icon }}"></i> @else {{ $data->block->avo }} @endif - {{ $data->block->name }}
                                        </span>
                                    </div>
                                    <?php $lastBlock = $data->block_id; ?>
                                @endif
                                <div class="flex px-2 py-1 bg-gray-100 rounded-sm w-full items-center">
                                    @if($data->rejection->reject ?? false)
                                        <i class="far fa-fw text-red-500 fa-times mr-2"></i>
                                    @else
                                        <i class="far fa-fw text-green-600 fa-check mr-2"></i>
                                    @endif
                                    <div class="flex flex-col">
                                        <span class="text-xs text-gray-500">{{ date('d.m.Y H:i', strtotime($data->created_at)) }} &nbsp;( <i class="fal fa-user"></i> {{ $data->operator }} )</span>
                                        <span class="text-sm @if($data->wafer_id == $wafer->id) font-semibold @else italic @endif @if($data->wafer->rejected) line-through @endif">{{ $data->wafer_id }}</span>
                                        @if($data->rejection->reject ?? false)
                                            <span class="text-xs text-red-500">{{ $data->rejection->name }}</span>
                                        @endif
                                        @if($data->reworked)
                                            <span class="text-xs text-orange-500">Wurde hier als Nacharbeit markiert</span>
                                        @endif
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
