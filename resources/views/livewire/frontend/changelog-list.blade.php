<?php
    use Livewire\Attributes\Layout;
    use Livewire\Volt\Component;
    use App\Models\Frontend\Changelog;

    new #[Layout('layouts.app')] class extends Component {
        public function addLog($title, $content) {
            if($content) {
                Changelog::create([
                    'user_id' => auth()->id(),
                    'title' => $title,
                    'content' => $content,
                ]);
            }
        }

        public function removeLog($id) {
            Changelog::destroy($id);
        }

        public function with() {
            $changelogs = Changelog::orderBy('created_at', 'DESC')->with('user')->get();

            return compact('changelogs');
        }
    }
?>

<div class="flex h-full mx-auto">
    <div class="flex flex-col p-4 w-full overflow-y-auto">
        <div class="flex flex-col gap-4">
            @foreach($changelogs as $log)
                <div class="bg-white ">
                    <div class="border-b border-gray-200 flex justify-between items-center px-4 py-2">
                        <div class="flex flex-col">
                            <span class="text-gray-400 text-xs">{{ $log->user->name }} am {{ $log->created_at->format('d.m.Y H:i') }}</span>
                            <h1 class="text-xl font-bold">{{ $log->title }}</h1>
                        </div>
                        @if(auth()->user()->is_admin)
                            <a href="javascript:" wire:click="removeLog({{ $log->id }})" class="text-xl text-red-500"><i class="fal fa-trash"></i></a>
                        @endif
                    </div>
                    <div class="p-4">
                        {!! $log->content !!}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @if(auth()->user()->is_admin)
        <div wire:ignore class="max-w-lg w-full flex flex-col min-w-lg p-4 bg-white border-l border-gray-200" x-data="{ editorValue: '', title: '' }">
            <h1 class="text-lg mb-2 font-semibold">Log hinzufügen</h1>
            <input type="text" x-model="title" class="mb-2 bg-gray-100 rounded-sm border-none font-semibold text-sm focus:ring-[#0085CA]" placeholder="Titel"/>
            <textarea id="tinyEditor" x-model="editorValue">

            </textarea>
            <a href="javascript:" @click="$wire.addLog(title, tinymce.activeEditor.getContent())" class="bg-[#0085CA] rounded-sm text-sm font-semibold uppercase px-3 py-1 text-white mt-2">Log hinzufügen</a>
        </div>
    @endif
</div>

@assets
    <script src="https://cdn.tiny.cloud/1/h6uktvbmku9vx92hi54xzfljo7gcp11mvyoab0vfjwdi0u5m/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
@endassets

@script
    <script>
        tinymce.init({
            selector: '#tinyEditor',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
        });
    </script>
@endscript
