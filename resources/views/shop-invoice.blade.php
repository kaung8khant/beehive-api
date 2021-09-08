<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <style>
        .mm-font {
            font-family: 'Tharlon';
        }

        .center {
            text-align: center;
        }

        .text-muted {
            color: #666;
        }

        .pdf-table {
            width: 100%;
            font-weight: light;
            line-height: 1.3em;
        }

        h2 {
            margin-top: 0 !important;
        }

        th,
        td {
            padding: 5px,
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
        <img src="{{ public_path() . '/beehive-logo.png' }}" alt="shop-logo" width="100px">
        <h2>Beehive</h2>
        <div>Kamayut, Yangon</div>
        <div>beehive@gmail.com</div>
        <div>09962223334</div>
    </div>
    <hr>
    <table align="right">
        <tbody>
            <tr>
                <td><b>INVOICE NO.</b></td>
                <td>{{ $shopOrder['invoice_id'] }}</td>
            </tr>
            <tr>
                <td><b>Date.</b></td>
                <td>{{ $date }}</td>
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
                <td>NAME</td>
                <td>:</td>
                <td>{{ $contact['customer_name'] }}</td>
            </tr>
            <tr>
                <td>PHONE</td>
                <td>:</td>
                <td>{{ $contact['phone_number'] }}</td>
            </tr>
            <tr>
                <td>ADDRESS</td>
                <td>:</td>
                <td>{{ $contact['street_name'] }}</td>
            </tr>
        </tbody>
    </table>
    <br />

    <table class="pdf-table" cellspacing="0">
        <thead>
            <tr>
                <th class="border" align="left">Product</th>
                <th class="border" align="left">Shop</th>
                <th class="border" align="right">Unit Price</th>
                <th class="border" align="right">Qty</th>
                <th class="border" align="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vendors as $vendor)
            @foreach ($vendor['items'] as $item)
            <tr>
                <td class="border mm-font" align="left">
                    {{ $item['product_name'] }}

                    @if($item['variant'])
                    <div class="text-muted">
                        {{
                            implode(',', array_map(function ($n) {
                                return $n['value'];
                            }, $item['variant']))
                        }}
                    </div>
                    @endif
                </td>
                <td class="border" align="left" style="vertical-align: top;">{{ $vendor['shop']['name'] }}</td>
                <td class="border" align="right" style="vertical-align: top;">{{ number_format($item['amount'] - $item['discount']) }} MMK</td>
                <td class="border" align="right" style="vertical-align: top;">{{ $item['quantity'] }}</td>
                <td class="border" align="right" style="vertical-align: top;">{{ number_format(($item['amount'] - $item['discount']) * $item['quantity']) }} MMK</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" align="right">Sub Total</td>
                <td align="right">{{ number_format(round($shopOrder['amount'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right">Delivery Fee</td>
                <td align="right">{{ 0 }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right">Tax</td>
                <td align="right">{{ number_format(round($shopOrder['tax'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right" class="border">Discount</td>
                <td align="right" class="border">{{ number_format(round($shopOrder['promocode_amount'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right">Total</td>
                <td align="right">{{ number_format(round($shopOrder['total_amount'])) }} MMK</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>