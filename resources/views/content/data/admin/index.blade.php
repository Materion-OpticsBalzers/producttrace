<x-app-layout>
    <div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 w-full">
        <h1 class="font-bold text-xl mb-4">Admin Panel</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.users') }}" class="rounded-md shadow-md bg-white p-6 hover:bg-gray-50 flex items-center">
                <i class="far fa-users mr-2"></i>
                <span class="font-semibold text-xl">Benutzerverwaltung</span>
            </a>
            <a href="{{ route('mappings.index') }}" class="rounded-md shadow-md bg-white p-6 hover:bg-gray-50 flex items-center">
                <i class="far fa-list mr-2"></i>
                <span class="font-semibold text-xl">Produkte verwalten</span>
            </a>
            <a href="{{ route('admin.formats') }}" class="rounded-md shadow-md bg-white p-6 hover:bg-gray-50 flex items-center">
                <i class="far fa-superscript mr-2"></i>
                <span class="font-semibold text-xl">Formate verwalten</span>
            </a>
        </div>
    </div>
</x-app-layout>
