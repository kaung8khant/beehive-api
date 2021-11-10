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
            line-height: 1.5rem;
        }

        .center {
            text-align: center;
        }

        h2 {
            margin-top: 5;
            margin-bottom: 5;
        }

        hr {
            margin: 5px auto;
        }

        .customer-detail {
            margin-left: 3px;
            margin-bottom: 15px;
        }

        h4 {
            margin-bottom: 5px;
        }

        .pdf-table {
            width: 100%;
            font-weight: light;
            font-size: 15px;
            line-height: 1.4em;
        }

        .border {
            border-bottom: 1px solid rgb(162, 164, 165);
        }

        .pdf-col {
            padding: 5px 3px;
        }

        .pdf-number {
            width: 5%;
        }

        .pdf-col-name {
            width: 30%;
            padding-right: 10px;
        }
    </style>
</head>

<body>
    <div class="center">
        <img src="{{ public_path() . '/beehive-logo.png' }}" alt="shop-logo" width="80px">
        <h2>Beehive</h2>
        <div>Kamayut, Yangon</div>
        <div>beehive@gmail.com</div>
        <div>09962223334</div>
    </div>

    <hr>

    <table style="width: 100%;">
        <tbody>
            <tr>
                <td></td>

                <td style="width: 12%;">
                    <strong>Invoice No.</strong>
                </td>

                <td style="width: 14%; text-align: right;">
                    {{ $shopOrder['invoice_id'] }}
                </td>
            </tr>
            <tr>
                <td></td>

                <td>
                    <strong>Date.</strong>
                </td>

                <td style="text-align: right;">
                    {{ $date }}
                </td>
            </tr>
        </tbody>
    </table>


    <div class="customer-detail">
        <h4>CUSTOMER DETAILS</h4>

        <div>
            <div style="width: 80px; float: left;"><strong>Name</strong></div>
            <div>: {{ $contact['customer_name'] }}</div>
        </div>

        <div>
            <div style="width: 80px; float: left;"><strong>Phone</strong></div>
            <div>: {{ $contact['phone_number'] }}</div>
        </div>

        <div>
            <div style="width: 80px; float: left;"><strong>Address</strong></div>
            <div>: {{ $contact['street_name'] }}</div>
        </div>
    </div>

    <table class="pdf-table" cellspacing="0">
        <thead>
            <tr>
                <th class="border pdf-col pdf-number" align="left">No.</th>
                <th class="border pdf-col pdf-col-name" align="left">Product</th>
                <th class="border pdf-col" align="left">Shop</th>
                <th class="border pdf-col" align="right">Unit Price</th>
                <th class="border pdf-col" style="width: 8%;" align="right">Qty</th>
                <th class="border pdf-col" style="width: 18%;" align="right">Line Total</th>
            </tr>
        </thead>

        <tbody>
            @php
            $currentLoop = 1;
            @endphp

            @foreach ($vendors as $vendor)
                @foreach ($vendor['items'] as $item)
                    <tr>
                        <td class="border pdf-col pdf-number" align="left" style="vertical-align: top;">{{ $currentLoop }}</td>
                        <td class="border pdf-col pdf-col-name" align="left">
                            {{ $item['product_name'] }}

                            @if($item['variant'])
                            <div>
                                {{
                                    implode(',', array_map(function ($n) {
                                        return $n['value'];
                                    }, $item['variant']))
                                }}
                            </div>
                            @endif
                        </td>
                        <td class="border pdf-col" align="left" style="vertical-align: top;">{{ $vendor['shop']['name'] }}</td>
                        <td class="border pdf-col" align="right" style="vertical-align: top;">{{ number_format($item['amount'] - $item['discount']) }} MMK</td>
                        <td class="border pdf-col" align="right" style="vertical-align: top;">{{ $item['quantity'] }}</td>
                        <td class="border pdf-col" align="right" style="vertical-align: top;">{{ number_format(($item['amount'] - $item['discount']) * $item['quantity']) }} MMK</td>
                    </tr>

                    @php
                        $currentLoop++;
                    @endphp
                @endforeach
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" align="right" class="pdf-col"><strong>Sub Total</strong></td>
                <td align="right" class="pdf-col">{{ number_format(round($shopOrder['amount'] - $shopOrder['discount']))}} MMK</td>
            </tr>
            <tr>
                <td colspan="5" align="right" class="pdf-col"><strong>Delivery Fee</strong></td>
                <td align="right" class="pdf-col">{{ 0 }} MMK</td>
            </tr>
            <tr>
                <td colspan="5" align="right" class="pdf-col"><strong>Tax</strong></td>
                <td align="right" class="pdf-col">{{ number_format(round($shopOrder['tax'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="5" align="right" class="border pdf-col"><strong>Discount</strong></td>
                <td align="right" class="border pdf-col">{{ number_format(round($shopOrder['promocode_amount'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="5" align="right" class="pdf-col"><strong>Total</strong></td>
                <td align="right" class="pdf-col">{{ number_format(round($shopOrder['total_amount'])) }} MMK</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
