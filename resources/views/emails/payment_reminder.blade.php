<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            padding: 20px;
        }
        .header {
            background: #2c3e50;
            color: #fff;
            padding: 15px 20px;
            border-radius: 4px;
        }
        .content {
            padding: 20px 0;
        }
        .invoice-box {
            background: #f8f9fa;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin: 20px 0;
            border-radius: 3px;
        }
        .invoice-box p { margin: 5px 0; }
        .amount {
            font-size: 22px;
            font-weight: bold;
            color: #e74c3c;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0">Payment Reminder</h2>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $invoice->customer->name }}</strong>,</p>

        <p>This is a friendly reminder that the following invoice
        is due for payment:</p>

        <div class="invoice-box">
            <p><strong>Invoice No:</strong>
               {{ $invoice->invoice_number }}</p>
            <p><strong>Invoice Date:</strong>
               {{ $invoice->invoice_date }}</p>
            <p><strong>Due Date:</strong>
               {{ $invoice->due_date }}</p>
            <p><strong>Amount Due:</strong></p>
            <p class="amount">
                ₹{{ number_format($invoice->total_amount, 2) }}
            </p>
        </div>

        <p>Please arrange payment at your earliest convenience
        to avoid any late payment charges.</p>

        <p>If you have already made the payment,
        please ignore this reminder.</p>

        <p>For any queries please contact us at
        <strong>{{ config('company.email', '') }}</strong> or
        <strong>{{ config('company.phone', '') }}</strong></p>
    </div>

    <div class="footer">
        <p>This is an automated reminder from
        {{ config('company.name', '') }}</p>
        <p>Please do not reply to this email.</p>
    </div>
</body>
</html>