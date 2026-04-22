<?php

namespace App\Jobs;

use App\Models\RecurringInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class GenerateRecurringInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $due = RecurringInvoice::where('status', 'active')
            ->where('next_billing_date', '<=', now()->toDateString())
            ->with('customer')
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        foreach ($due as $template) {
            // Create invoice from template
            $invoice = Invoice::create([
                'customer_id'     => $template->customer_id,
                'created_by'      => $template->created_by,
                'invoice_date'    => now()->toDateString(),
                'place_of_supply' => $template->customer->state_code,
                'status'          => 'draft',
                'notes'           => 'Auto-generated recurring invoice — '
                                     . $template->frequency,
            ]);

            // Create items from template
            $items = json_decode($template->item_templates, true);
            foreach ($items as $item) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'description'  => $item['description'],
                    'hsn_sac_code' => $item['hsn_sac_code'] ?? null,
                    'qty'          => $item['qty'],
                    'unit'         => $item['unit'] ?? 'NOS',
                    'unit_price'   => $item['unit_price'],
                    'discount_pct' => $item['discount_pct'] ?? 0,
                    'gst_rate'     => $item['gst_rate'] ?? 0,
                    'taxable_value'=> $item['qty'] * $item['unit_price'],
                    'total'        => $item['qty'] * $item['unit_price'],
                ]);
            }

            // Advance next billing date
            $template->next_billing_date = $this->advanceDate(
                $template->next_billing_date,
                $template->frequency
            );

            // Check if end date reached
            if ($template->end_date &&
                $template->next_billing_date > $template->end_date) {
                $template->status = 'cancelled';
            }

            $template->save();
        }
    }

    private function advanceDate(string $date, string $freq): string
    {
        $d = Carbon::parse($date);
        return match($freq) {
            'weekly'      => $d->addWeek()->toDateString(),
            'fortnightly' => $d->addWeeks(2)->toDateString(),
            'monthly'     => $d->addMonth()->toDateString(),
            'quarterly'   => $d->addMonths(3)->toDateString(),
            'half_yearly' => $d->addMonths(6)->toDateString(),
            'annually'    => $d->addYear()->toDateString(),
            default       => $d->addMonth()->toDateString(),
        };
    }
}
