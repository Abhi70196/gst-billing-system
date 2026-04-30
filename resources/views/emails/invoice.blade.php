<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
    .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .header { background: #1e293b; padding: 28px 32px; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; }
    .header p { color: #94a3b8; margin: 4px 0 0; font-size: 13px; }
    .body { padding: 32px; }
    .invoice-box { background: #f8fafc; border-radius: 8px; padding: 20px; margin: 20px 0; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
    .row:last-child { border-bottom: none; font-weight: 700; font-size: 16px; color: #1e293b; }
    .badge { display: inline-block; background: #dcfce7; color: #16a34a; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .btn { display: inline-block; background: #3b82f6; color: #fff; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; margin-top: 20px; }
    .footer { background: #f8fafc; padding: 20px 32px; font-size: 12px; color: #94a3b8; text-align: center; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>VaultGST</h1>
      <p>GST Billing & Invoicing</p>
    </div>
    <div class="body">
      <p style="font-size:15px;">Dear <strong>{{ $invoice->customer->name }}</strong>,</p>
      <p style="font-size:14px; color:#475569;">Please find your invoice details below. The PDF is attached to this email.</p>

      <div class="invoice-box">
        <div class="row"><span style="color:#64748b;">Invoice Number</span><span>{{ $invoice->invoice_number }}</span></div>
        <div class="row"><span style="color:#64748b;">Invoice Date</span><span>{{ $invoice->invoice_date }}</span></div>
        <div class="row"><span style="color:#64748b;">Due Date</span><span>{{ $invoice->due_date }}</span></div>
        <div class="row"><span style="color:#64748b;">Status</span><span class="badge">{{ ucfirst($invoice->status) }}</span></div>
        <div class="row"><span>Total Amount</span><span>₹{{ number_format($invoice->total_amount, 2) }}</span></div>
      </div>

      <p style="font-size:13px; color:#64748b;">If you have any questions, please reply to this email.</p>
      <p style="font-size:13px; color:#64748b;">Thank you for your business!</p>
    </div>
    <div class="footer">
      This is an automated email from VaultGST. Please do not reply directly.
    </div>
  </div>
</body>
</html>