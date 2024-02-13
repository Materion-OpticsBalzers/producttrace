<?php
    use App\Models\Data\Process;

    new class extends \Livewire\Volt\Component {
        public $block;
        public $order;

        public $search = '';

        public function with()
        {
            $wafers = Process::where('order_id', $this->order->id)->with(['rejection', 'block', 'wafer'])->whereHas('rejection', function($query) {
                return $query->where('reject', true);
            })->lazy();

            $waferCount = count(Process::where('order_id', $this->order->id)->select('wafer_id')->groupBy('wafer_id')->get());

            $wafers = $wafers->where('wafer.reworked', false)->sortBy('block.avo');

            if($wafers->count() > 0)
                $calculatedRejections = ($wafers->count() / $waferCount) * 100;
            else
                $calculatedRejections = 0;

            if($this->search != '') {
                $wafers = $wafers->filter(function ($value, $key) {
                    return stristr($value->wafer_id, $this->search);
                });
            }

            $rejections = [];
            $rejectionCounts = [];
            $prevRejection = "";
            $index = -1;
            $wafersT = $wafers->sortBy('rejection.name');
            foreach($wafersT as $wafer) {
                if($wafer->rejection->name != $prevRejection) {
                    $index++;
                    $prevRejection = $wafer->rejection->name;
                    $rejections[] = "'{$wafer->rejection->name}'";
                    $rejectionCounts[$index] = 1;
                } else {
                    $rejectionCounts[$index] += 1;
                }
            }

            $failedWafers = $wafers->count();
            foreach($rejectionCounts as $rejectionCountKey => $rejectionCountValue) {
                $rejectionCounts[$rejectionCountKey] = number_format(($rejectionCountValue / $failedWafers) * 100, 2);
            }

            return compact(['wafers', 'waferCount', 'calculatedRejections', 'rejections', 'rejectionCounts']);
        }
    }
?>

<div class="flex flex-col bg-white w-full h-full z-[9] border-l border-gray-200">
    <div class="px-8 py-3 text-lg font-semibold flex border-b border-gray-200 items-center z-[8]">
        <span class="font-extrabold text-lg mr-2"><i class="far fa-ban"></i></span>
        <span class="grow">{{ $block->name }}</span>
    </div>
    <div class="px-8 py-1 font-semibold shadow-sm flex border-b border-gray-200 items-center z-[8]">
        Ausschuss in diesem Auftrag: <span class="mx-1 text-red-500 text-lg font-bold">{{ $wafers->count() }}</span> / <span class="mx-1 text-lg font-bold">{{ $waferCount }}</span> <span class="font-bold @if($calculatedRejections > 70) text-red-500 @elseif($calculatedRejections > 50) text-orange-500 @elseif($calculatedRejections < 30) text-yellow-400 @else text-green-600 @endif ml-1">({{ number_format($calculatedRejections, 2) }} %)</span>
    </div>
    <div class="p-2" id="chart">
    </div>
    <script>
        function docReady(fn) {
            // see if DOM is already available
            if (document.readyState === "complete" || document.readyState === "interactive") {
                // call on next available tick
                setTimeout(fn, 1);
            } else {
                document.addEventListener("DOMContentLoaded", fn);
            }
        }

        docReady(function() {
            new ApexCharts(document.querySelector("#chart"), {
               chart: {
                   type: 'bar',
                   height: 200,
                   toolbar: {
                       show: true,
                       tools: {
                           selection: true,
                           zoom: true,
                           pan: true
                       }
                   },
                   zoom: {
                       enabled: true,
                       type: 'x'
                   }
               },
                colors: ['#f25344'],
                plotOptions: {
                   bar: {
                       horizontal: true,
                       dataLabels: {
                           position: 'top',
                       }
                   }
                },
                dataLabels: {
                    formatter: function(value) {
                        return value + '%';
                    },
                    offsetX: -10
                },
                series: [
                    {
                        name: 'Ausschuss',
                        data: [{{ join(',', $rejectionCounts) }}]
                    }
                ],
                grid: {
                    position: 'back',
                    strokeDashArray: 7,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                xaxis: {
                    categories: [{!! join(',', $rejections) !!}],
                    labels: {
                        formatter: function(value) {
                            return value + '%';
                        }
                    },
                    tickPlacement: 'on',
                    lines: {
                       show: true
                    }
                },
                yaxis: {
                    max: 100,
                },
                tooltip: {
                   y: {
                       formatter: function(value) {
                           return value + '%';
                       }
                   }
                }
            }).render();
        });
    </script>
    <div class="h-full bg-gray-100 flex z-[7] overflow-y-auto">
        <div class="w-full px-4 py-3 flex flex-col">
            <input type="text" wire:model.live.debounce.500ms="search" onfocus="this.setSelectionRange(0, this.value.length)" class="bg-white rounded-sm mb-1 text-sm font-semibold shadow-sm w-full border-0 focus:ring-[#0085CA]" placeholder="Wafer durchsuchen..." />
            <div class="flex flex-col gap-1 mt-2" wire:loading.remove.delay.longer wire:target="search">
                <div class="px-2 py-1 rounded-sm grid grid-cols-3 items-center justify-between bg-gray-200 shadow-sm mb-1">
                    <span class="text-sm font-bold"><i class="fal fa-hashtag mr-1"></i> Wafer</span>
                    <span class="text-sm font-bold"><i class="fal fa-map-marker-alt mr-1"></i> Position</span>
                    <span class="text-sm font-bold"><i class="fal fa-clock mr-1"></i> Datum</span>
                </div>
                @forelse($wafers as $wafer)
                    <div @click="waferOpen = !waferOpen" class="cursor-pointer px-2 py-1 bg-white border @if($wafer->rejection->reject) border-red-500/50 @else border-green-600/50 @endif flex flex-col rounded-sm hover:bg-gray-50 justify-center" x-data="{ waferOpen: false }">
                        <div class="flex grow items-center">
                            <i class="fal fa-chevron-down mr-2" x-show="!waferOpen"></i>
                            <i class="fal fa-chevron-up mr-2" x-show="waferOpen"></i>
                            <div class="flex flex-col grow">
                                <div class="grid grid-cols-3 items-center">
                                    <span class="text-sm font-semibold">{{ $wafer->wafer_id }}</span>
                                    <span class="text-xs">{{ $wafer->block->avo }} - {{ $wafer->block->name }}</span>
                                    <span class="text-xs text-gray-500 truncate">{{ date('d.m.Y H:i', strtotime($wafer->created_at)) }}</span>
                                </div>
                                <span class="text-xs font-normal grow text-red-500">Ausschuss: <b>{{ $wafer->rejection->name }}</b></span>
                            </div>
                        </div>
                        <div class="p-2 flex flex-col border-t mt-2 border-gray-200 text-xs" x-show="waferOpen">
                            <p class="text-gray-500 italic"><i class="fal fa-exclamation-triangle mr-1 text-red-500"></i> Dieser Wafer wurde als Ausschuss markiert. Der Wafer kann in keinem Auftrag und Bearbeitungsschritt mehr verwendet werden!</p>
                            <div class="flex flex-col gap-1 mt-2">
                                <span>Auftrag: <span class="font-semibold">{{ $this->order->id }} <a href="{{ route('orders.show', ['order' => $wafer->order_id]) }}" class="ml-1 italic text-[#0085CA]"><i class="fal fa-link"></i> Öffnen</a></span></span>
                                <span>Aussschussgrund: <span class="font-semibold text-red-500">{{ $wafer->rejection->name }}</span></span>
                                <span>Bearbeitungsschritt: <span class="font-semibold">{{ $wafer->block->avo }} - {{ $wafer->block->name }} <a href="{{ route('blocks.show', ['order' => $wafer->order_id, 'block' => $wafer->block->identifier]) }}" class="ml-1 italic text-[#0085CA]"><i class="fal fa-link"></i> Öffnen</a></span></span>
                                <span>Operator: <span class="font-semibold">{{ $wafer->operator }}</span></span>
                                <span>Datum: <span class="font-semibold text-gray-500">{{ $wafer->created_at }}</span></span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col justify-center items-center p-10">
                        <span class="text-lg font-bold text-red-500">Keine Wafer gefunden!</span>
                        <span class="text-sm text-gray-500">Es wurden keine Wafer in diesem Arbeitsschritt gefunden.</span>
                    </div>
                @endforelse
            </div>
            <div class="flex flex-col justify-center items-center p-10 animate-pulse text-center" wire:loading.delay.longer wire:target="search">
                <span class="text-lg font-bold text-red-500">Wafer werden geladen...</span><br>
                <span class="text-sm text-gray-500">Die Wafer werden geladen, wenn dieser Vorgang zu lange dauert bitte die Seite neu laden.</span>
            </div>
        </div>
    </div>
</div>
