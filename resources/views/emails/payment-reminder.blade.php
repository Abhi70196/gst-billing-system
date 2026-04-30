<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; background: #f4f4f4; }
    .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .header { background: #dc2626; padding: 28px 32px; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; }
    .header p { color: #fca5a5; margin: 4px 0 0; font-size: 13px; }
    .body { padding: 32px; }
    .invoice-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 20px; margin: 20px 0; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; border-bottom: 1px solid #fee2e2; }
    .row:last-child { border-bottom: none; font-weight: 700; font-size: 16px; color: #dc2626; }
    .footer { background: #f8fafc; padding: 20px 32px; font-size: 12px; color: #94a3b8; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>⚠️ Payment Reminder</h1>
      <p>VaultGST Billing</p>
    </div>
    <div class="body">
      <p style="font-size:15px;">Dear <strong>{{ $invoice->customer->name }}</strong>,</p>
      <p style="font-size:14px; color:#475569;">This is a friendly reminder that the following invoice is pending payment.</p>

      <div class="invoice-box">
        <div class="row"><span style="color:#64748b;">Invoice Number</span><span>{{ $invoice->invoice_number }}</span></div>
        <div class="row"><span style="color:#64748b;">Invoice Date</span><span>{{ $invoice->invoice_date }}</span></div>
        <div class="row"><span style="color:#64748b;">Due Date</span><span>{{ $invoice->due_date }}</span></div>
        <div class="row"><span style="color:#64748b;">Amount Paid</span><span>₹{{ number_format($invoice->amount_paid ?? 0, 2) }}</span></div>
        <div class="row"><span>Balance Due</span><span>₹{{ number_format($invoice->total_amount - ($invoice->amount_paid ?? 0), 2) }}</span></div>
      </div>

      <p style="font-size:13px; color:#64748b;">Please arrange payment at your earliest convenience. If you have already made the payment, please ignore this reminder.</p>
    </div>
    <div class="footer">
      VaultGST — Automated Payment Reminder
    </div>
  </div>
</body>
</html>