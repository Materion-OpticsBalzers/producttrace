<x-app-layout>
    <div class="w-full h-full flex flex-col z-[11]">
        <livewire:data.fast-panel-wafer />
        <div class="shadow-md px-8 py-2.5 flex flex-col mt-28 bg-gray-50 z-[11]">
            <div class="flex items-center gap-4">
                <span class="text-xl font-bold">{{ $wafer->id }}</span>
                <span class="text-sm text-gray-500"><i class="fal fa-upload mr-1"></i> Importiert {{ $wafer->created_at->diffForHumans() }}</span>
            </div>
            <div class="flex">
                @if($wafer->rejected)
                    <span class="text-xs">Ausschuss: <span class="text-red-500">{{ $wafer->rejection_reason }} in {{ $wafer->rejection_order }} <i class="fal fa-arrow-right"></i> {{ $wafer->rejection_avo }} - {{ $wafer->rejection_position }}</span></span>
                @else
                    <span class="text-xs text-green-600">Dieser Wafer ist in Ordnung</span>
                @endif
            </div>
        </div>
        <div class="flex h-full w-full overflow-hidden">
            <div class="flex flex-col gap-2 z-[10] p-2 w-full px-8 overflow-y-auto">
                <h1 class="font-semibold text-lg">Historie</h1>
                @forelse($waferOrders as $order)
                    <div class="px-8 py-2 flex flex-col bg-white border border-gray-200 shadow-sm">
                        <div class="flex flex-col">
                            <span class="font-semibold text-lg">{{ $order->order_id }}</span>
                            <span class="text-sm text-gray-500">{{ $order->order->mapping->product->name }}</span>
                        </div>
                        <div class="flex flex-col mt-2 gap-1">
                            @foreach($waferData->where('order_id', $order->order_id) as $data)
                                <div class="px-3 py-1 flex flex-col">
                                <span class="@if($data->rejection->reject ?? false) text-red-500 @endif font-semibold">
                                    @if($data->block->icon != '') <i class="fal {{ $data->block->icon }}"></i> @else {{ $data->block->avo }} @endif - {{ $data->block->name }}
                                </span>
                                    @if($data->rejection->reject ?? false) <span class="text-xs text-gray-400">Der Wafer ist hier aus dem Prozess herausgeflogen</span> @endif
                                    <div class="flex flex-col border border-gray-200 bg-gray-100 rounded-sm mt-1 p-2">
                                    <span class="text-sm font-semibold flex gap-2 items-center">
                                        {{ $data->wafer_id }}
                                        <span class="text-xs text-gray-600"><i class="fal fa-box-open"></i> {{ $data->box }}</span>
                                        <span class="text-xs text-gray-600"><i class="fal fa-user"></i> {{ $data->operator }}</span>
                                        <span class="text-xs text-gray-600"><i class="fal fa-plus"></i> {{ date('d.m.Y H:i', strtotime($data->created_at)) }}</span>
                                        <span class="text-xs text-gray-600"><i class="fal fa-pencil"></i> {{ date('d.m.Y H:i', strtotime($data->updated_at)) }}</span>
                                    </span>
                                        <div class="flex text-gray-600">
                                            @if($data->rejection->reject ?? false)
                                                <span class="text-xs text-red-500 font-semibold">{{ $data->rejection->name }}</span>
                                            @else
                                                <span class="text-xs text-green-600 font-semibold">Wafer gut</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
