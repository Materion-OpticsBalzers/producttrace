<?php

namespace App\Http\Livewire\Frontend;

use Livewire\Component;
use App\Models\Frontend\Changelog;

class ChangelogList extends Component
{
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

    public function render()
    {
        $changelogs = Changelog::orderBy('created_at', 'DESC')->with('user')->get();

        return view('livewire.frontend.changelog-list', compact('changelogs'));
    }
}
