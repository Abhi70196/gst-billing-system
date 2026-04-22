<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Mail\PaymentReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    protected $signature   = 'invoices:send-reminders';
    protected $description = 'Send payment reminder emails for overdue invoices';

    public function handle(): void
    {
        $overdueInvoices = Invoice::with('customer')
            ->whereIn('status', ['finalised', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereHas('customer', fn($q) => 
                $q->where('reminder_opt_out', false)
            )
            ->where(fn($q) =>
                $q->whereNull('reminder_sent_at')
                  ->orWhereRaw('DATEDIFF(CURDATE(), reminder_sent_at) >= 7')
            )->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            return;
        }

        foreach ($overdueInvoices as $invoice) {
            if (!$invoice->customer?->email) continue;

            Mail::to($invoice->customer->email)
                ->queue(new PaymentReminderMail($invoice));

            $invoice->reminder_sent_at = now();
            $invoice->save();

            $this->info('Queued reminder for: ' . $invoice->invoice_number);
        }

        $this->info('Done. Processed ' . $overdueInvoices->count() . ' invoices.');
    }
}