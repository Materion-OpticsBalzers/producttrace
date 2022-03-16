<?php

namespace App\Events;

use App\Models\Data\Order;
use App\Models\Generic\Block;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaferScanned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $block;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Block $block)
    {
        $this->block = $block;
    }

    public function broadcastAs() {
        return 'wafer.scanned';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('scanWafer.' . $this->block->id);
    }
}
