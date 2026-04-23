<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-info h2 {
            margin: 0;
            font-size: 16px;
        }
        .company-info p {
            margin: 1px 0;
            font-size: 10px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h3 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
        }
        .invoice-info p {
            margin: 1px 0;
            font-size: 10px;
        }
        .bill-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .bill-box {
            width: 48%;
        }
        .bill-box h4 {
            margin: 0 0 3px 0;
            font-size: 10px;
            text-transform: uppercase;
            background: #333;
            color: #fff;
            padding: 3px 6px;
        }
        .bill-box p {
            margin: 1px 0;
            font-size: 10px;
            padding: 0 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table th {
            background: #333;
            color: #fff;
            padding: 5px 6px;
            font-size: 10px;
            text-align: left;
        }
        table td {
            padding: 4px 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        .totals-row {
            display: flex;
            justify-content: flex-end;
        }
        .totals-table {
            width: 250px;
        }
        .totals-table td {
            padding: 3px 6px;
            font-size: 11px;
        }
        .totals-table td:last-child {
            text-align: right;
        }
        .grand-total {
            font-weight: bold;
            border-top: 2px solid #333;
            font-size: 12px !important;
        }
        .footer {
            margin-top: 15px;
            font-size: 9px;
            color: #999;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h2>{{ config('company.name', 'Your Company') }}</h2>
            <p>{{ config('company.address', '') }}</p>
            <p>GSTIN: {{ config('company.gstin', '') }}</p>
            <p>{{ config('company.phone', '') }} | {{ config('company.email', '') }}</p>
        </div>
        <div class="invoice-info">
            <h3>Tax Invoice</h3>
            <p><strong>No:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
            <p><strong>Due:</strong> {{ $invoice->due_date ?? 'N/A' }}</p>
            <p><strong>Status:</strong> {{ strtoupper($invoice->status) }}</p>
        </div>
    </div>

    <div class="bill-section">
        <div class="bill-box">
            <h4>Bill To</h4>
            <p><strong>{{ $invoice->customer->name }}</strong></p>
            <p>{{ $invoice->customer->billing_address }}</p>
            <p>GSTIN: {{ $invoice->customer->gstin ?? 'N/A' }}</p>
        </div>
        <div class="bill-box">
            <h4>Supply Details</h4>
            <p>Place of Supply: {{ $invoice->place_of_supply }}</p>
            <p>Reverse Charge: {{ $invoice->reverse_charge ? 'Yes' : 'No' }}</p>
            <p>Export: {{ $invoice->is_export ? 'Yes' : 'No' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th>HSN</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Disc%</th>
                <th>Taxable</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>IGST</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->hsn_sac_code }}</td>
                <td>{{ $item->qty }}</td>
                <td>₹{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ $item->discount_pct }}%</td>
                <td>₹{{ number_format($item->taxable_value, 2) }}</td>
                <td>₹{{ number_format($item->cgst_amount, 2) }}</td>
                <td>₹{{ number_format($item->sgst_amount, 2) }}</td>
                <td>₹{{ number_format($item->igst_amount, 2) }}</td>
                <td>₹{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-row">
        <table class="totals-table">
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

    <div class="footer">
        <p>{{ config('company.name', '') }} | Computer generated invoice | {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>