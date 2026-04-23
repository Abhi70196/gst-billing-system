<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #222;
        }
        .header p {
            margin: 2px 0;
            font-size: 12px;
        }
        .invoice-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-box {
            width: 48%;
        }
        .info-box h3 {
            margin: 0 0 5px 0;
            font-size: 13px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        .info-box p {
            margin: 2px 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background: #333;
            color: #fff;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        table td {
            padding: 7px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .totals table td {
            padding: 5px 8px;
        }
        .totals table td:last-child {
            text-align: right;
        }
        .grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333 !important;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 11px;
            color: #666;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid     { background: #d4edda; color: #155724; }
        .badge-draft    { background: #fff3cd; color: #856404; }
        .badge-finalised{ background: #cce5ff; color: #004085; }
        .badge-cancelled{ background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    {{-- Company Header --}}
    <div class="header">
        <h1>{{ config('company.name', 'Your Company Name') }}</h1>
        <p>{{ config('company.address', 'Company Address') }}</p>
        <p>GSTIN: {{ config('company.gstin', 'GSTIN HERE') }} 
           | Phone: {{ config('company.phone', '') }}
           | Email: {{ config('company.email', '') }}</p>
    </div>

    {{-- Invoice Title --}}
    <div class="invoice-title">Tax Invoice</div>

    {{-- Invoice Info --}}
    <div class="info-section">
        <div class="info-box">
            <h3>Bill To</h3>
            <p><strong>{{ $invoice->customer->name }}</strong></p>
            <p>{{ $invoice->customer->billing_address }}</p>
            <p>GSTIN: {{ $invoice->customer->gstin ?? 'N/A' }}</p>
            <p>Phone: {{ $invoice->customer->phone ?? 'N/A' }}</p>
        </div>
        <div class="info-box">
            <h3>Invoice Details</h3>
            <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date ?? 'N/A' }}</p>
            <p><strong>Status:</strong> 
                <span class="badge badge-{{ $invoice->status }}">
                    {{ $invoice->status }}
                </span>
            </p>
            <p><strong>Place of Supply:</strong> {{ $invoice->place_of_supply }}</p>
        </div>
    </div>

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
                <td>{{ $item->discount_pct }}%</td>
                <td>₹{{ number_format($item->taxable_value, 2) }}</td>
                <td>
                    @if($item->igst_amount > 0)
                        IGST {{ $item->igst_rate }}%
                    @else
                        CGST {{ $item->cgst_rate }}% + 
                        SGST {{ $item->sgst_rate }}%
                    @endif
                </td>
                <td>₹{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="tota