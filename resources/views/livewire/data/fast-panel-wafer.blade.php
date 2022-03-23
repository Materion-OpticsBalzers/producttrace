<div class="w-full h-12 flex fixed mt-16 font-bold py-2 bg-gray-50 shadow-sm items-center z-[11] border-b border-gray-200">
    <div class="w-fit mr-4">
        <a href="{{ route('dashboard') }}" class="ml-8 whitespace-nowrap"><i class="far fa-arrow-left mr-1"></i> Zurück zur Startseite</a>
    </div>
    <div class="flex items-center w-full pr-1" x-data="{ wafer: '' }">
        <input type="text" x-model="wafer" @keyup.enter="$wire.scanWafer(wafer)" class="bg-gray-200 font-semibold text-sm rounded-sm h-9 border-0 @error('wafer') border-1 border-red-500 @enderror w-full focus:ring-[#0085CA]" placeholder="Anderen Wafer öffnen...">
        <button class="ml-1 bg-[#0085CA] text-white px-3 py-1 uppercase rounded-sm h-9">Suchen</button>
    </div>
</div>
