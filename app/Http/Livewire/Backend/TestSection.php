<?php

namespace App\Http\Livewire\Backend;

use App\Events\WaferScanned;
use App\Models\Generic\Block;
use Livewire\Component;

class TestSection extends Component
{
    public function testBroadcast() {
        $block = Block::find(10);

        WaferScanned::dispatch($block);
    }

    public function render()
    {
        return view('livewire.backend.test-section');
    }
}
