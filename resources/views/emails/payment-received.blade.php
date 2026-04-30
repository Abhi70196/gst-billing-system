<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; background: #f4f4f4; }
    .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .header { background: #16a34a; padding: 28px 32px; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; }
    .header p { color: #bbf7d0; margin: 4px 0 0; font-size: 13px; }
    .body { padding: 32px; }
    .invoice-box { background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 20px 0; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; border-bottom: 1px solid #bbf7d0; }
    .row:last-child { border-bottom: none; font-weight: 700; font-size: 16px; color: #16a34a; }
    .footer { background: #f8fafc; padding: 20px 32px; font-size: 12px; color: #94a3b8; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>✅ Payment Received</h1>
      <p>VaultGST Billing</p>
    </div>
    <div class="body">
      <p style="font-size:15px;">Dear <strong>{{ $invoice->customer->name }}</strong>,</p>
      <p style="font-size:14px; color:#475569;">We have received your payment. Thank you!</p>

      <div class="invoice-box">
        <div class="row"><span style="color:#166534;">Invoice Number</span><span>{{ $invoice->invoice_number }}</span></div>
        <div class="row"><span style="color:#166534;">Invoice Date</span><span>{{ $invoice->invoice_date }}</span></div>
        <div class="row"><span style="color:#166534;">Amount Paid</span><span>₹{{ number_format($invoice->amount_paid ?? 0, 2) }}</span></div>
        <div class="row"><span>Total Paid</span><span>₹{{ number_format($invoice->total_amount, 2) }}</span></div>
      </div>

      <p style="font-size:13px; color:#64748b;">Thank you for your prompt payment. We look forward to doing business with you!</p>
    </div>
    <div class="footer">
      VaultGST — Payment Confirmation
    </div>
  </div>
</body>
</html>