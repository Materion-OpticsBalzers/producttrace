<?php

class BlockHelper extends \MyCLabs\Enum\Enum{
    const BLOCK_INCOMING_QUALITY_CONTROL = 1;
    const BLOCK_CHROMIUM_COATING = 2;
    const BLOCK_LITHO = 4;
    const BLOCK_BEFORE_MICROSCOPE = 5;
    const BLOCK_MICROSCOPE_AOI = 6;
    const BLOCK_INCOMING_QUALITY_CONTROL_AR = 10;
    const BLOCK_ARC = 8;
    const BLOCK_OUTGOING_QUALITY_CONTROL = 9;

    public static function getPrevAndNextBlock($order, $blockId) : object {
        foreach($order->mapping->blocks as $b) {
            if(!isset($b->type)) {
                if($b->id == $blockId) {
                    return (object) [
                        'prev' => $b->prev ?? null,
                        'next' => $b->next ?? null
                    ];
                }
            }
        }

        return (object) [
            'prev' => null,
            'next' => null
        ];
    }
}
