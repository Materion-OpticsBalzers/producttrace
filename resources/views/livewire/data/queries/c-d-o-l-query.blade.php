<div class="h-full max-w-6xl min-w-6xl mx-auto pt-4 pb-4 w-full">
    <h1 class="font-bold text-xl">Filter</h1>
    <div class="flex flex-col mt-1">
        <div class="flex justify-evenly mt-2 gap-2">
            <input type="date" wire:model.live="dateFrom" class="w-full" placeholder="Datum von" />
            <input type="date" wire:model.live="dateTo" class="w-full" placeholder="Datum bis" />
        </div>
    </div>

    <div class="h-full flex w-full justify-center pt-20" wire:loading>
        <h1 class="text-[#0085CA] font-bold text-xl">Chart wird generiert...</h1>
    </div>
    @if($wafers->count() > 0)
        <h1 class="font-bold text-xl mt-4">Chart</h1>
        <span>von {{ $this->dateFrom }} bis {{ $this->dateTo ?? 'Heute' }}</span>
        <div class="p-2 bg-white mt-2 rounded-sm" id="chart" wire:loading.remove>
        </div>
        <script>
            window.addEventListener('paretoChanged', function() {
                document.getElementById('chart').innerHTML = ""
                generatePareto();
            })

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
                generatePareto()
            });

            function generatePareto() {
                new ApexCharts(document.querySelector("#chart"), {
                    chart: {
                        type: 'line',
                        height: 400,
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
                    title: {
                        text: 'Werteverlauf: CD OL & CD UR'
                    },
                    stroke: {
                        width: 1,
                    },
                    colors: ['#ef4444', '#3b82f6'],
                    series: [
                        {
                            name: 'CD OL',
                            data: [{{ join(',', $values) }}]
                        },
                        {
                            name: 'CD UR',
                            data: [{{ join(',', $values2) }}]
                        }
                    ],
                    grid: {
                        position: 'back',
                        strokeDashArray: 0,
                        xaxis: {
                            lines: {
                                show: true
                            }
                        }
                    },
                    xaxis: {
                        type: 'category',
                        categories: [{!! join(', ', $valueLabels) !!}],
                        labels: {
                          show: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    },
                    yaxis: {
                        max: 7.0,
                        min: 3.0,
                        tickAmount:20,
                        labels: {
                            formatter: (value) => { return value.toFixed(1) }
                        },
                        title: {
                            text: 'µm'
                        },
                        axisTicks: {
                            show: true
                        }
                    },
                    tooltip: {
                        x: {
                            show: true
                        }
                    }
                }).render();
            }
        </script>
    @else
        <h1 class="font-bold text-red-500 mt-4">Keine Daten für diese Filtereinstellungen gefunden</h1>
    @endif
</div>
