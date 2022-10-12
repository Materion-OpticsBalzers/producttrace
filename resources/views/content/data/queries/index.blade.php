<x-app-layout>
    <div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 pb-4 w-full">
        <h1 class="font-bold text-xl">Auswertungstyp auswählen</h1>
        <div class="grid grid-cols-4 mt-2 gap-2">
            <a href="{{ route('queries.pareto') }}" class="rounded-md bg-white p-4 hover:bg-gray-50">
                <h1 class="font-semibold text-lg">Paretos</h1>
            </a>
            <a href="{{ route('queries.cdol') }}" class="rounded-md bg-white p-4 hover:bg-gray-50">
                <h1 class="font-semibold text-lg">Control Chart - Critical Dimension</h1>
            </a>
        </div>
    </div>
</x-app-layout>
