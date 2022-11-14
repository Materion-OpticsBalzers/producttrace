<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            /*@media print {
                html * {
                    visibility: hidden;
                }

                #printArea, #printArea * {
                    visibility: visible;
                }

                #printArea {
                    position: absolute;
                    left: 0;
                    top: 0;
                }
            }*/

            @page {
                size: 21cm 29.7cm; margin: 0; padding: 0;
            }

            .label {
                position: absolute;
                font-size: 11px;
                border-radius: 5px;
                height: 215.4px;
                width: 374.5px;
                left: 18.8px;
                top: 22.6px;
            }

            .absolute {
                position: absolute;
            }

            .label-2 {
                left: 402.7px;
            }

            .label-3 {
                top: 238px;
            }

            .label-4 {
                top: 238px;
                left: 402.7px;
            }

            .label-5 {
                top: 453.4px;
            }

            .label-6 {
                top: 453.4px;
                left: 402.7px;
            }

            .label-7 {
                top: 668.8px;
            }

            .label-8 {
                top: 668.8px;
                left: 402.7px;
            }

            .label-9 {
                top: 884.2px;
            }

            .label-10 {
                top: 884.2px;
                left: 402.7px;
            }
        </style>
    </head>
    <body>
        <!--
         top/bottom: 22.6px
         left/right: 18.8px
         gap: 9.4px
         -->
        @for($i = $startPos;$i < 10;$i++)
            @if($wafers[$i] != null)
                <?php $wafer = $wafers[$i]; ?>
                <div class="label label-{{ $i + 1 }}">
                    <img class="absolute" style="top: 7px; right: 7px;" src="{{ public_path('img/logo.png') }}" height="70" width="70"/>
                    <span class="absolute" style="top: 20px; left: 10px">Life Technologies Holding Ltd. Pre.</span>
                    <span class="absolute" style="top: 60px; left: 10px">Lifetech P/O</span>
                    <span class="absolute" style="top: 60px; left: 100px">{{ $wafer->po_cust  }}</span>
                    <span class="absolute" style="top: 74px; left: 10px">Lifetech P/N</span>
                    <span class="absolute" style="top: 74px; left: 100px">{{ $wafer->article_cust  }}</span>
                    <span class="absolute" style="top: 110px; left: 10px">Balzers Ref#</span>
                    <span class="absolute" style="top: 110px; left: 100px">{{ $wafer->po  }}</span>
                    <span class="absolute" style="top: 124px; left: 10px">Balzers P/N</span>
                    <span class="absolute" style="top: 124px; left: 100px">{{ $wafer->article  }}</span>
                    <span class="absolute" style="top: 170px; left: 10px;">Substrate ID: </span>
                    <span class="absolute" style="top: 170px; left: 100px;">{{ $wafer->serials->first()->id }} - {{ $wafer->serials->last()->id }}</span>
                </div>
            @endif
        @endfor
    </body>
</html>
