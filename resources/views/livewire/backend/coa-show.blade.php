<div class="h-full w-full overflow-y-auto">
    <div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 pb-4 mb-4 w-full">
        <h1 class="text-xl font-bold">CofA für {{ $order->id }}</h1>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold flex border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">Informationen</span>
            <div class="flex flex-col p-2">
                @if(!$order->po) <span class="rounded-md px-2 py-1 bg-orange-100 text-orange-500 font-semibold text-xs">Dieser Auftrag wurde noch nicht serialisiert</span> @endif
                <div class="grid grid-cols-2 text-sm mt-2 bg-gray-100 rounded-md p-2">
                    <span><b>Customer P.O. No.:</b> {{ $order->po_cust }}</span>
                    <span><b>Date:</b> {{ \Carbon\Carbon::now()->format('d.m.Y') }}</span>
                    <span><b>Life Tech Part No.:</b> {{ $order->article_cust }}</span>
                    <span><b>Optics Ref. No.:</b> {{ $order->po }}</span>
                    <span><b>Optics Part No.:</b> {{ $order->article }}</span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold flex border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">Positionen</span>
            <div class="flex flex-col p-2">
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
                        <span class="py-0.5 text-xs">{{ $serial->id }}</span>
                        <span class="py-0.5 text-xs">{{ substr($serial->wafer->processes->first()->position ?? '?', 0, 1) }}</span>
                        <span class="py-0.5 text-xs">{{ str_replace('-r', '', $serial->wafer_id) }}</span>
                        <span class="py-0.5 text-xs">{{ $serial->wafer->order->supplier ?? '?' }}</span>
                        <span class="py-0.5 text-xs">{{ $serial->wafer->processes->first()->lot ?? 'chrom fehlt' }}</span>
                        <span class="py-0.5 text-xs">{{ $serial->wafer->processes->first()->machine ?? 'chrom fehlt' }}</span>
                        <span class="py-0.5 text-xs">{{ $serial->wafer->processes->get(1)->machine ?? 'ar fehlt' }}</span>
                        <span class="py-0.5 text-xs">{{ $serial->wafer->processes->get(2)->machine ?? 'ar fehlt' }}</span>
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md shadow-sm mt-2">
            <span class="font-semibold items-center flex justify-between border-b rounded-t-md bg-gray-200 border-gray-100 px-2 py-1">
                Chromdaten
                <span class="text-xs"><i class="fal fa-database mr-1"></i> CAQ</span>
            </span>
            <div class="flex flex-col p-2">

            </div>
        </div>
    </div>
</div>
