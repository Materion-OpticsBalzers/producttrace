<div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 pb-4 w-full">
    <h1 class="font-bold text-xl">Pareto Auswertung erstellen</h1>
    <div class="flex flex-col mt-2">
        <select wire:model="selectedBlock" class="">
            <option value="0" disabled selected>Schritt auswählen</option>
            @foreach($blocks as $block)
                <option value="{{ $block->id }}">{{ $block->avo }} - {{ $block->name }}</option>
            @endforeach
        </select>
        <div class="flex justify-evenly mt-2 gap-2">
            <input type="date" wire:model="dateFrom" class="w-full" placeholder="Datum von" />
            <input type="date" wire:model="dateTo" class="w-full" placeholder="Datum bis" />
        </div>
    </div>

    <div class="h-full flex w-full justify-center pt-20" wire:loading>
        <h1 class="text-[#0085CA] font-bold text-xl">Pareto wird generiert...</h1>
    </div>
    @if(sizeof($rejections) > 0)
        <h1 class="font-bold text-xl mt-4">Pareto ({{ $wafers->first()->block->name }})</h1>
        <span>von {{ $this->dateFrom }} bis {{ $this->dateTo ?? 'Heute' }}</span>
        <div class="p-2 bg-white mt-2 rounded-sm" id="chart{{ $this->selectedBlock }}" wire:loading.remove>
        </div>
        <script>
            window.addEventListener('paretoChanged', function() {
                document.getElementById('chart{{ $this->selectedBlock }}').innerHTML = ""
                generatePareto();
            })

            function generatePareto() {
                new ApexCharts(document.querySelector("#chart{{$this->selectedBlock}}"), {
                    chart: {
                        type: 'bar',
                        height: 100 * {{ sizeof($rejections) }},
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
                            return value;
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
                                return value;
                            }
                        },
                        tickPlacement: 'on',
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        max: 50,
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return value;
                            }
                        }
                    }
                }).render();
            }
        </script>
    @else
        <h1 class="font-bold text-red-500 mt-4">Keine Daten für diese Filtereinstellungen gefunden</h1>
    @endif
</div>
