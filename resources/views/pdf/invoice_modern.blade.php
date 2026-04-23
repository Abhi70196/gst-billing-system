<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #444;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .top-bar {
            background: #2c3e50;
            color: #fff;
            padding: 25px 30px;
        }
        .top-bar h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 1px;
        }
        .top-bar p {
            margin: 3px 0;
            font-size: 12px;
            color: #bdc3c7;
        }
        .invoice-badge {
            float: right;
            background: #e74c3c;
            color: #fff;
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }
        .body-content {
            padding: 25px 30px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        .info-box {
            width: 48%;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            border-left: 4px solid #2c3e50;
        }
        .info-box h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            text-transform: uppercase;
            color: #2c3e50;
            letter-spacing: 1px;
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
            background: #2c3e50;
            color: #fff;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table td {
            padding: 9px 10px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 12px;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .totals {
            float: right;
            width: 280px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
        }
        .totals table td {
            padding: 6px 8px;
            border: none;
        }
        .totals table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .grand-total td {
            background: #2c3e50;
            color: #fff !important;
            font-size: 14px;
            border-radius: 3px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <span class="invoice-badge">TAX INVOICE</span>
        <h1>{{ config('company.name', 'Your Company') }}</h1>
        <p>{{ config('company.address', '') }}</p>
        <p>GSTIN: {{ config('company.gstin', '') }} | {{ config('company.email', '') }}</p>
    </div>

    <div class="body-content">
        <div class="info-section">
            <div class="info-box">
                <h3>Bill To</h3>
                <p><strong>{{ $invoice->customer->name }}</strong></p>
                <p>{{ $invoice->customer->billing_address }}</p>
                <p>GSTIN: {{ $invoice->customer->gstin ?? 'N/A' }}</p>
            </div>
            <div class="info-box">
                <h3>Invoice Details</h3>
                <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date ?? 'N/A' }}</p>
                <p><strong>Place of Supply:</strong> {{ $invoice->place_of_supply }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>HSN/SAC</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Taxable</th>
                    <th>GST</th>
                    <th>Total</th>
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
                    <td>₹{{ number_format($item->taxable_value, 2) }}</td>
                    <td>
                        @if($item->igst_amount > 0)
                            IGST {{ $item->igst_rate }}%
                        @else
                            CGST+SGST {{ $item->cgst_rate }}%
                        @endif
                    </td>
                    <td>₹{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

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

        <div class="footer">
            <p>Thank you for your business! | {{ config('company.name', '') }}</p>
            <p>This is a computer generated invoice.</p>
        </div>
    </div>
</body>
</html>