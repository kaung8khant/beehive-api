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
                <td class="info-col"><b>INVOICE NO.</b></td>
                <td class="info-col">{{ $shopOrder['invoice_id'] }}</td>
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
                <td class="info-col">{{ $contact['customer_name'] }}</td>
            </tr>
            <tr>
                <td class="info-col">PHONE</td>
                <td class="info-col">:</td>
                <td class="info-col">{{ $contact['phone_number'] }}</td>
            </tr>
            <tr>
                <td class="info-col">ADDRESS</td>
                <td class="info-col">:</td>
                <td class="info-col">{{ $contact['street_name'] }}</td>
            </tr>
        </tbody>
    </table>
    <br />

    <table class="pdf-table" cellspacing="0">
        <thead>
            <tr>
                <th class="border pdf-col" align="left">Product</th>
                <th class="border pdf-col" align="left">Shop</th>
                <th class="border pdf-col" align="right">Unit Price</th>
                <th class="border pdf-col" align="right">Qty</th>
                <th class="border pdf-col" align="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vendors as $vendor)
            @foreach ($vendor['items'] as $item)
            <tr>
                <td class="border pdf-col" align="left">
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
            @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" align="right" class="pdf-col">Sub Total</td>
                <td align="right" class="pdf-col">{{ number_format(round($shopOrder['amount'] - $shopOrder['discount']))}} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right" class="pdf-col">Delivery Fee</td>
                <td align="right" class="pdf-col">{{ 0 }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right" class="pdf-col">Tax</td>
                <td align="right" class="pdf-col">{{ number_format(round($shopOrder['tax'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right" class="border pdf-col">Discount</td>
                <td align="right" class="border pdf-col">{{ number_format(round($shopOrder['promocode_amount'])) }} MMK</td>
            </tr>
            <tr>
                <td colspan="4" align="right" class="pdf-col">Total</td>
                <td align="right" class="pdf-col">{{ number_format(round($shopOrder['total_amount'])) }} MMK</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
