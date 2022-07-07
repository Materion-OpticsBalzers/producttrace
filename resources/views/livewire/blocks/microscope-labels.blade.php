<div class="flex flex-col bg-white w-full h-full pt-28 z-[9] border-l border-gray-200" x-data="">
    <div class="pl-8 pr-4 py-3 text-lg font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2"><i class="far fa-tag"></i></span>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="flex divide-x divide-gray-200 w-full h-full">
        <div class="flex flex-col grow divide-y divide-gray-200 overflow-y-auto pb-4 z-[7]" x-data="{ selectedWafers: @entangle('selectedWafers') }">
            @foreach($wafers as $wafer)
                <label class="flex gap-2 items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" value="{{ $wafer->ar_box }}" x-model="selectedWafers"/>
                    AR Box ID: {{ $wafer->ar_box }}
                </label>
            @endforeach
        </div>
        <div class="flex flex-col relative min-w-xl max-w-xl shrink-0 h-full w-full p-4 overflow-x-visible z-[8]">
            <div class="absolute w-full h-full bg-white bg-opacity-50 z-[9]" wire:loading wire:target="print"></div>

            <div class="flex justify-between items-center z-[8]">
                <h1 class="text-lg font-semibold"><i class="fal fa-eye"></i> Druckvorschau</h1>
                <a href="javascript:" wire:click="print" class="bg-[#0085CA] rounded-sm px-2 py-1 uppercase hover:bg-[#0085CA]/80 text-white text-sm w-max font-semibold">Etiketten Drucken</a>
            </div>
            @error('print')
                <span class="text-xs font-semibold text-red-500">{{ $message }}</span>
            @enderror
            <div class="grid grid-cols-2 grid-rows-5 gap-[2px] bg-gray-200 rounded-sm mt-4 p-[4px] mb-16 shrink-0 h-[574px] w-[508px]">
                @foreach($selectedWs as $selectedW)
                    <div class="bg-white border relative border-gray-300 h-[114px] w-[250px] shadow-sm rounded-sm hover:bg-gray-50 cursor-pointer hover:scale-150 hover:z-[8]">
                        <img class="absolute top-2 right-2" src="{{ asset('img/logo.png') }}" height="50" width="50"/>
                        <span class="absolute top-[15px] left-2 text-[7px] flex items-center">Life Technologies Holdings Pre. Ltd.</span>
                        <span class="absolute top-[35px] left-2 text-[7px] flex gap-4 items-center">Artikelnummer <span>{{ $order->article }}</span></span>
                        <span class="absolute top-[44px] left-2 text-[7px] flex gap-4 items-center">PAS Format <span>{{ $order->article_desc }}</span></span>
                        <span class="absolute top-[50px] right-2 text-[7px] flex gap-3 items-center">Datum <span>{{ $order->created_at->format('d/m/y') }}</span></span>
                        <span class="absolute top-[60px] left-2 text-[7px] flex gap-4 items-center">Box ID <span>1</span></span>
                        <span class="absolute top-[78px] left-2 text-[7px] flex items-center gap-2">Chrom Chargen <span class="text-[6px]">{{ $selectedW->lots->join(', ') }}</span></span>
                        <span class="absolute top-[78px] right-2 text-[7px] flex gap-1 items-center">Menge <span>{{ $selectedW->count }}</span></span>
                        <span class="absolute top-[87px] left-2 text-[7px] flex items-center">Box ID Chrom</span>
                        <span class="absolute top-[96px] left-2 text-[7px] flex items-center gap-2">Auftragsnummern <span class="text-[6px]">{{ $selectedW->orders->join(', ') }}</span></span>
                    </div>
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
                    }, 1);
                };
            }

            iframe.src = url;
        }

        window.addEventListener('printPdf', function (filename) {
            printPdf(filename.detail)
        })
    </script>
</div>
