<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <style>
        body {
            font-family: 'Tharlon';
        }

        .center {
            text-align: center;
        }

        .pdf-table {
            width: 100%;
            font-weight: light;
            line-height: 1.3em;
        }

        h2 {
            margin-top: 0 !important;
        }

        .info-col{
            padding:5px;
        }
        .pdf-col {
            padding: 5px 15px 5px 15px !important;
            width: 20%;
        }

        .pdf-col-name {
            width: 40%;
        }

        .border {
            border-bottom: 1px solid rgb(162, 164, 165);
        }

        tfoot {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="center">
        <img src="{{ public_path() . '/beehive-logo.png' }}" alt="restaurant-logo" width="100px">
        <h2>Beehive</h2>
        <div>Kamayut, Yangon</div>
        <div>beehive@gmail.com</div>
        <div>09962223334</div>
    </div>
    <hr>
    <h4>{{ $branchInfo['restaurant']['name'] }}
        <div> ({{ $branchInfo['name'] }})</div>
    </h4>
    <table align="right">
        <tbody>
            <tr>
                <td class="info-col"><b>INVOICE NO.</b></td>
                <td class="info-col">{{ $restaurantOrder['invoice_id'] }}</td>
            </tr>
            <tr>
                <td class="info-col"><b>Date.</b></td>
                <td class="info-col">{{ $date }}</td>
            </tr>

        </tbody>
    </table>
    <br />
    <br />
    <br />
    <h5>CUSTOMER DETAILS</h5>
    <table>
        <tbody>
            <tr>
                <td class="info-col">NAME</td>
                <td class="info-col">:</td>
                <td class="info-col">{{ $restaurantOrderContact['customer_name'] }}</td>
            </tr>
            <tr>
                <td class="info-col">PHONE</td>
                <td class="info-col">:</td>
                <td class="info-col">{{ $restaurantOrderContact['phone_number'] }}</td>
            </tr>
            <tr>
                <td class="info-col">ADDRESS</td>
                <td class="info-col">:</td>
                <td class="info-col">{{ $restaurantOrderContact['street_name'] }}</td>
            </tr>
        </tbody>
    </table>
    <br />

    <table class="pdf-table" cellspacing="0">
        <thead>
            <tr>
                <th class="border pdf-col pdf-col-name" align="left">Menu</th>
                <th class="border pdf-col" align="right">Unit Price</th>
                <th class="border pdf-col" align="right">Qty</th>
                <th class="border pdf-col" align="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($restaurantOrderItems as $item)
            <tr>
                <td class="border pdf-col pdf-col-name" align="left">
                    {{ $item['menu_name'] }}

                    @if($item['variant'])
                    <div>
                        {{
                            implode(',', array_map(function ($n) {
                                return $n['value'];
                            }, $item['variant']))
                        }}
                    </div>
                    @endif

                    @if($item['toppings'])
                    @foreach ($item['toppings'] as $t)
                    <div>
                        {{ implode(' ', $t) }}
                    </div>
                    @endforeach
                    @endif

                    @if($item['options'])
                    <div>
                        {{
                            implode(',', array_map(function ($n) {
                                return $n['name'];
                            }, $item['options']))
                        }}
                    </div>
                    @endif
                </td>
                <td class="border pdf-col" align="right" style="vertical-align: top;">{{ number_format($item['amount'] - $item['discount']) }} MMK</td>
                <td class="border pdf-col" align="right" style="vertical-align: top;">{{ $item['quantity'] }}</td>
                <td class="border pdf-col" align="right" style="vertical-align: top;">{{ number_format(($item['amount'] - $item['discount']) * $item['quantity']) }} MMK</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" align="right" class="pdf-col">Sub Total</td>
                <td align="right" class="pdf-col">{{ number_format(round($restaurantOrder['amount'] - $restaurantOrder['discount'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="3" align="right" class="pdf-col">Delivery Fee</td>
                <td align="right" class="pdf-col">{{ 0 }} MMK</td>
            </tr>
            <tr>
                <td colspan="3" align="right" class="pdf-col">Tax</td>
                <td align="right" class="pdf-col">{{ number_format(round($restaurantOrder['tax'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="3" align="right" class="border pdf-col">Discount</td>
                <td align="right" class="border pdf-col">{{ number_format(round($restaurantOrder['promocode_amount'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="3" align="right" class="pdf-col">Total</td>
                <td align="right" class="pdf-col">{{ number_format(round($restaurantOrder['total_amount'])) }} MMK</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
