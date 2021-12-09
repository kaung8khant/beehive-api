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
            margin-bottom: 20px;
            display: inline-block;
            width: 100%;
        }

        h4 {
            margin-bottom: 5px;
        }

        .pdf-table {
            width: 100%;
            font-weight: light;
            font-size: 15px;
            line-height: 1.4em;
            border-collapse: collapse;

        }
        .pdf-col{
                border-bottom: 1px solid rgb(162, 164, 165);
                padding: 5px 10px;
            }
            .pdf-footer-col{
                padding: 5px 10px;
            }

            .border{
                border-top: 1px solid rgb(162, 164, 165);
            }

            .pdf-amount-col{
                white-space: nowrap;
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
                <td style="width: 14%;">
                    <strong>Order No:</strong>
                </td>
                <td style="width: 14%; text-align: right;">
                    {{ $shopOrder['order_no'] }}
                </td>
            </tr>

            @if ($shopOrder['invoice_no'])
            <tr>
                <td></td>
                <td>
                    <strong>Invoice No:</strong>
                </td>
                <td style="width: 14%; text-align: right;">
                    {{ $shopOrder['invoice_no'] }}
                </td>
            </tr>
            @endif

            <tr>
                <td></td>
                <td>
                    <strong>Order Date:</strong>
                </td>
                <td style="text-align: right;">
                    {{ $date }}
                </td>
            </tr>

            @if ($shopOrder['invoice_date'])
            <tr>
                <td></td>
                <td>
                    <strong>Invoice Date:</strong>
                </td>
                <td style="text-align: right;">
                    {{ date_format($shopOrder['invoice_date'], 'd M Y') }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="customer-detail">
        <h4>CUSTOMER DETAILS</h4>

        <div>
            <div style="width: 90px; float: left;"><strong>Name</strong></div>
            <div>: {{ $contact['customer_name'] }}</div>
        </div>

        <div>
            <div style="width: 90px; float: left;"><strong>Phone</strong></div>
            <div>: {{ $contact['phone_number'] }}</div>
        </div>

        <div>
            <div style="width: 90px; float: left;"><strong>Address</strong></div>
            <div>: {{ $contact['street_name'] }}</div>
        </div>
      @if($shopOrder['special_instruction'])   <div>
            <div style="width: 90px; float: left;"><strong>Special Instructions</strong></div>
            <div>: {{ $shopOrder['special_instruction'] }}</div>
        </div>
        @endif
    </div>

    <table class="pdf-table">
        <thead>
            <tr>
                <th class="pdf-col" align="left">No.</th>
                <th class="pdf-col" align="left">Product</th>
                <th class="pdf-col" align="left">Shop</th>
                <th class="pdf-col" align="right">Unit Price</th>
                <th class="pdf-col" align="right">Qty</th>
                <th class="pdf-col" align="right">Line Total</th>
            </tr>
        </thead>

        <tbody>
            @php
                $currentLoop = 1;
            @endphp

            @foreach ($vendors as $vendor)
                @foreach ($vendor['items'] as $item)

                <tr>
                    <td class="pdf-col" align="left" style="vertical-align: top;">
                        {{ $currentLoop }}
                    </td>
                    <td class="pdf-col" align="left">
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
                    <td class="pdf-col" align="left" style="vertical-align: top;">
                        {{ $vendor['shop']['name'] }}
                    </td>
                    <td class="pdf-col pdf-amount-col" align="right" style="vertical-align: top;">
                        {{ number_format($item['amount'] - $item['discount']) }} MMK
                    </td>
                    <td class="pdf-col" align="right" style="vertical-align: top;">
                        {{ $item['quantity'] }}
                    </td>
                    <td class="pdf-col pdf-amount-col" align="right" style="vertical-align: top;">
                        {{ number_format(($item['amount'] - $item['discount']) * $item['quantity']) }} MMK
                    </td>
                </tr>

                @php
                    $currentLoop++;
                @endphp

                @endforeach
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" align="right" class="pdf-footer-col">
                    <strong>Sub Total</strong>
                </td>
                <td align="right" class="pdf-footer-col pdf-amount-col">
                    {{ number_format(round($shopOrder['amount'] - $shopOrder['discount']))}} MMK
                </td>
            </tr>

            <tr>
                <td colspan="5" align="right" class="pdf-footer-col">
                    <strong>Delivery Fee</strong>
                </td>
                <td align="right" class="pdf-footer-col pdf-amount-col">
                    {{ 0 }} MMK
                </td>
            </tr>

            <tr>
                <td colspan="5" align="right" class="pdf-footer-col">
                    <strong>Tax</strong>
                </td>
                <td align="right" class="pdf-footer-col pdf-amount-col">
                    {{ number_format(round($shopOrder['tax'])) }} MMK
                </td>
            </tr>
            @if(number_format(round($shopOrder['promocode_amount'])))
            <tr>
                <td colspan="5" align="right" class="pdf-footer-col">
                    <strong>Promotion</strong>
                </td>
                <td align="right" class="pdf-footer-col pdf-amount-col">
                    {{ number_format(round($shopOrder['promocode_amount'])) }} MMK
                </td>
            </tr>
@endif
            <tr>
                <td colspan="4" class="pdf-footer-col">
                </td>
                <td align="right" class="pdf-footer-col border">
                    <strong>Total</strong>
                </td>
                <td align="right" class="pdf-footer-col border pdf-amount-col">
                    {{ number_format(round($shopOrder['total_amount'])) }} MMK
                </td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
