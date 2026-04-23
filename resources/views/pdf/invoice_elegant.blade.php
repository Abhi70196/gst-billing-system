<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 13px;
            color: #2c2c2c;
            margin: 0;
            padding: 30px;
            background: #fff;
        }
        .border-frame {
            border: 2px solid #c9a84c;
            padding: 25px;
            min-height: 95vh;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #c9a84c;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #c9a84c;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .header p {
            margin: 3px 0;
            font-size: 11px;
            color: #666;
        }
        .invoice-title {
            text-align: center;
            font-size: 16px;
            letter-spacing: 5px;
            text-transform: uppercase;
            color: #c9a84c;
            margin: 15px 0;
            font-weight: normal;
        }
        .divider {
            border: none;
            border-top: 1px solid #c9a84c;
            margin: 15px 0;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .info-box {
            width: 48%;
        }
        .info-box h3 {
            margin: 0 0 8px 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #c9a84c;
            border-bottom: 1px solid #c9a84c;
            padding-bottom: 4px;
        }
        .info-box p {
            margin: 3px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background: #c9a84c;
            color: #fff;
            padding: 9px 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: normal;
        }
        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f0e6cc;
            font-size: 12px;
        }
        table tr:nth-child(even) td {
            background: #fdf8ee;
        }
        .totals {
            float: right;
            width: 290px;
        }
        .totals table th {
            background: none;
            color: #2c2c2c;
            font-size: 12px;
            padding: 5px 8px;
        }
        .totals table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f0e6cc;
        }
        .totals table td:last-child {
            text-align: right;
        }
        .grand-total td {
            background: #c9a84c !important;
            color: #fff !important;
            font-size: 14px;
            font-weight: bold;
            border: none !important;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #f0e6cc;
            border-radius: 3px;
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #c9a84c;
            border-top: 1px solid #c9a84c;
            padding-top: 12px;
            font-style: italic;
        }
        .stamp {
            float: right;
            text-align: center;
            margin-top: 20px;
            width: 150px;
        }
        .stamp p {
            font-size: 10px;
            color: #666;
            margin: 40px 0 0 0;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="border-frame">

        {{-- Header --}}
        <div class="header">
            <h1>{{ config('company.name', 'Your Company') }}</h1>
            <p>{{ config('company.address', '') }}</p>
            <p>GSTIN: {{ config('company.gstin', '') }}
               &nbsp;|&nbsp; {{ config('company.phone', '') }}
               &nbsp;|&nbsp; {{ config('company.email', '') }}
            </p>
        </div>

        <div class="invoice-title">✦ Tax Invoice ✦</div>
        <hr class="divider">

        {{-- Info Section --}}
        <div class="info-section">
            <div class="info-box">
                <h3>Billed To</h3>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                <p>{{ $invoice->customer->billing_address }}</p>
                <p>GSTIN: {{ $invoice->customer->gstin ?? 'N/A' }}</p>
                <p>Phone: {{ $invoice->customer->phone ?? 'N/A' }}</p>
            </div>
            <div class="info-box" style="text-align:right">
                <h3>Invoice Details</h3>
                <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date ?? 'N/A' }}</p>
                <p><strong>Place of Supply:</strong> {{ $invoice->place_of_supply }}</p>
                @if($invoice->reverse_charge)
                <p><strong>Reverse Charge:</strong> Yes</p>
                @endif
            </div>
        </div>

        <hr class="divider">

        {{-- Items Table --}}
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>HSN/SAC</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
                    <th>Taxable Value</th>
                    <th>GST</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->hsn_sac_code }}</td>
                    <td>{{ $item->qty }} {{ $item->unit }}</td>
                    <td>₹{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->discount_pct }}%</td>
                    <td>₹{{ number_format($item->taxable_value, 2) }}</td>
                    <td>
                        @if($item->igst_amount > 0)
                            IGST {{ $item->igst_rate }}%
                        @else
                            CGST {{ $item->cgst_rate }}%
                            + SGST {{ $item->sgst_rate }}%
                        @endif
                    </td>
                    <td>₹{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <table>
                <tr><td>Subtotal</td><td>₹{{ number_format($invoice->subtotal, 2) }}</td></tr>
                @if($invoice->cgst_total > 0)
                <tr><td>CGST</td><td>₹{{ number_format($invoice->cgst_total, 2) }}</td></tr>
                <tr><td>SGST</td><td>₹{{ number_format($invoice->sgst_total, 2) }}</td></tr>
                @endif
                @if($invoice->igst_total > 0)
                <tr><td>IGST</td><td>₹{{ number_format($invoice->igst_total, 2) }}</td></tr>
                @endif
                @if($invoice->cess_total > 0)
                <tr><td>CESS</td><td>₹{{ number_format($invoice->cess_total, 2) }}</td></tr>
                @endif
                <tr class="grand-total">
                    <td>Grand Total</td>
                    <td>₹{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear:both"></div>

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="notes">
            <strong>Notes:</strong> {{ $invoice->notes }}
        </div>
        @endif

        {{-- Stamp --}}
        <div class="stamp">
            <p>Authorised Signatory</p>
        </div>

        <div style="clear:both"></div>

        {{-- Footer --}}
        <div class="footer">
            <p>Thank you for your valued business — {{ config('company.name', '') }}</p>
            <p style="font-size:10px; color:#999;">
                This is a computer generated invoice and does not require a physical signature.
            </p>
        </div>

    </div>
</body>
</html>