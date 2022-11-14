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
                    <span class="absolute" style="top: 60px; left: 10px">Artikelnummer</span>
                    <span class="absolute" style="top: 60px; left: 100px">{{ $wafer->article  }}</span>
                    <span class="absolute" style="top: 74px; left: 10px">PAS Format</span>
                    <span class="absolute" style="top: 74px; left: 100px">{{ $wafer->format  }}</span>
                    <span class="absolute" style="top: 84px; right: 10px">Datum &nbsp;&nbsp;{{ $wafer->date->format('m/d/y') }}</span>
                    <span class="absolute" style="top: 105px; left: 10px">AR Box ID</span>
                    <span class="absolute" style="top: 105px; left: 100px">{{ $wafer->ar_box  }}</span>
                    <span class="absolute" style="top: 121px; left: 10px">{!! \Milon\Barcode\DNS1D::getBarcodeHTML($wafer->ar_box, 'C128', 2, 20) !!}</span>
                    <span class="absolute" style="top: 154px; left: 10px">Chrom Charge/n</span>
                    <span class="absolute" style="top: 154px; left: 100px; font-size: 10px">{{ $wafer->lots->join(', ')  }}</span>
                    <span class="absolute" style="top: 154px; right: 10px;">Menge &nbsp;&nbsp;{{ $wafer->count  }}</span>
                    <span class="absolute" style="top: 168px; left: 10px">Chrom Box ID</span>
                    <span class="absolute" style="top: 168px; left: 10px"></span>
                    <span class="absolute" style="top: 182px; left: 10px">Auftragsnummer/n</span>
                    <span class="absolute" style="top: 182px; left: 100px; font-size: 10px">{{ $wafer->orders->join(', ')  }}</span>
                </div>
            @endif
        @endfor
    </body>
</html>
