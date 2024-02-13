<?php
    use Livewire\Attributes\Layout;
    use App\Models\Generic\Format;

    new #[Layout('layouts.app')] class extends \Livewire\Volt\Component {
        public function addFormat($name, $identifier, $dimension, $tolerance) {
            $this->resetErrorBag();

            if(!$name) {
                $this->addError('name', 'Der Name des Formats darf nicht leer sein!');
                return false;
            }

            if(!$identifier) {
                $this->addError('identifier', 'Die Kennung des Formats darf nicht leer sein!');
                return false;
            }

            $format = Format::where('name', $identifier)->first();
            if($format != null) {
                $this->addError('identifier', 'Ein anderes Format mit dieser Kennung ist schon vorhanden');
                return false;
            }

            if($dimension == '') {
                $this->addError('dimension', 'Die Dimension des Formats darf nicht leer sein!');
                return false;
            }

            if($tolerance == '') {
                $this->addError('tolerance', 'Die Toleranz des Formats darf nicht leer sein!');
                return false;
            }

            Format::create([
                'title' => $name,
                'name' => $identifier,
                'min' => $dimension - $tolerance,
                'max' => $dimension + $tolerance
            ]);
        }

        public function saveFormat($id, $title, $name, $min, $max) {
            Format::find($id)->update([
                'title' => $title,
                'name' => $name,
                'min' => $min,
                'max' => $max
            ]);
        }

        public function removeFormat($id) {
            Format::destroy($id);
        }

        public function with()
        {
            $formats = Format::all();

            return compact('formats');
        }
    }
?>

<div class="h-full w-full flex">
    <div class="flex flex-col border-r border-gray-200 bg-white max-w-sm min-w-sm w-full p-4" x-data="{ name: '', identifier: '', dimension: 0, tolerance: 250 }">
        <h1 class="text-xl font-semibold mb-2">Format hinzufügen</h1>
        <label class="text-sm font-semibold">Name</label>
        <input type="text" x-model="name" class="bg-gray-100 shadow-sm rounded-sm font-semibold border-none focus:ring-[#0085CA]" placeholder="Name" />
        @error('name') <span class="text-xs mt-1 text-red-500">{{ $message }}</span> @enderror
        <label class="mt-2 text-sm font-semibold">Kennung</label>
        <input type="text" x-model="identifier" class="bg-gray-100 shadow-sm rounded-sm font-semibold border-none focus:ring-[#0085CA]" placeholder="Kennung" />
        @error('identifier') <span class="text-xs mt-1 text-red-500">{{ $message }}</span> @enderror
        <label class="mt-2 text-sm font-semibold">Dimension</label>
        <input type="text" x-model="dimension" class="bg-gray-100 shadow-sm rounded-sm font-semibold border-none focus:ring-[#0085CA]" placeholder="Dimension" />
        @error('dimension') <span class="text-xs mt-1 text-red-500">{{ $message }}</span> @enderror
        <label class="mt-2 text-sm font-semibold">Toleranz</label>
        <input type="text" x-model="tolerance" class="bg-gray-100 shadow-sm rounded-sm font-semibold border-none focus:ring-[#0085CA]" placeholder="Toleranz" />
        @error('tolerance') <span class="text-xs mt-1 text-red-500">{{ $message }}</span> @enderror
        <a href="javascript:" @click="$wire.addFormat(name, identifier, dimension, tolerance)" class="mt-2 bg-[#0085CA] text-white rounded-sm font-semibold text-sm hover:bg-[#0085CA]/80 uppercase px-3 py-2">Hinzufügen</a>
    </div>
    <div class="p-4 overflow-y-auto w-full">
        <h1 class="text-xl font-semibold">Formate verwalten</h1>
        <div class="bg-white flex flex-col rounded-sm mt-4 pb-2">
            <div class="grid grid-cols-5 p-2 font-semibold sticky -top-4 shadow-sm bg-white mb-2">
                <span>Name</span>
                <span>Kennung</span>
                <span>Dimension Min</span>
                <span>Dimension Max</span>
                <span></span>
            </div>
            @forelse($formats as $format)
                <div class="grid grid-cols-5 px-2 items-center py-1 text-sm" x-data="{ id: {{ $format->id }}, title: '{{ $format->title }}', name: '{{ $format->name }}', min: {{ $format->min }}, max: {{ $format->max }} }">
                    <span><input x-model="title" class="text-xs bg-gray-100 rounded-sm font-semibold border-none focus:ring-[#0085CA]" type="text"/></span>
                    <span><input x-model="name" class="text-xs bg-gray-100 rounded-sm font-semibold border-none focus:ring-[#0085CA]" type="text"/></span>
                    <span><input x-model="min" class="text-xs bg-gray-100 rounded-sm font-semibold border-none focus:ring-[#0085CA]" type="text"/></span>
                    <span><input x-model="max" class="text-xs bg-gray-100 rounded-sm font-semibold border-none focus:ring-[#0085CA]" type="text"/></span>
                    <span class="flex gap-2 text-lg justify-end">
                        <a href="javascript:"@click="$wire.saveFormat(id, title, name, min, max);"><i class="fal fa-save"></i></a>
                        <a href="javascript:" wire:click="removeFormat(id)" class="text-red-500"><i class="fal fa-trash"></i></a>
                    </span>
                </div>
            @empty
            @endforelse
        </div>
    </div>

</div>
