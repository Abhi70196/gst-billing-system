<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Mail\InvoiceMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PaymentReceivedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailController extends Controller
{
    // Send invoice email with PDF attached
    public function sendInvoice(Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->email) {
            return response()->json(['message' => 'Customer email not found.'], 422);
        }

        try {
            // Generate PDF path if you have PDF generation set up
            $pdfPath = null; // Will attach PDF when PDF system is ready

            Mail::to($customer->email)->send(new InvoiceMail($invoice, $pdfPath));

            return response()->json(['message' => 'Invoice email sent to ' . $customer->email]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    // Send payment reminder
    public function sendReminder(Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->email) {
            return response()->json(['message' => 'Customer email not found.'], 422);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'Invoice is already paid.'], 422);
        }

        try {
            Mail::to($customer->email)->send(new PaymentReminderMail($invoice));
            return response()->json(['message' => 'Payment reminder sent to ' . $customer->email]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send reminder: ' . $e->getMessage()], 500);
        }
    }

    // Send payment received confirmation
    public function sendPaymentConfirmation(Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->email) {
            return response()->json(['message' => 'Customer email not found.'], 422);
        }

        try {
            Mail::to($customer->email)->send(new PaymentReceivedMail($invoice));
            return response()->json(['message' => 'Payment confirmation sent to ' . $customer->email]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send confirmation: ' . $e->getMessage()], 500);
        }
    }
}