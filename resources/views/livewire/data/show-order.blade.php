<?php
    use Livewire\Attributes\Layout;
    use App\Models\Generic\Block;

    new #[Layout('layouts.app')] class extends \Livewire\Volt\Component {
        public function mount(\App\Models\Data\Order $order) {
            $this->redirect(route('blocks.show', ['order' => $order->id, 'block' => $order->mapping->init_block]), true);
        }
    }
?>

<div class="flex w-full h-full">

</div>
