<?php

namespace App\Services;

use App\Models\FeeInvoice;

class FeeInvoiceCalculator
{
    public function refresh(FeeInvoice $invoice): FeeInvoice
    {
        if ($invoice->status === 'void') {
            return $invoice;
        }

        $subtotal = round((float) $invoice->items()->sum('amount'), 2);
        $discount = min(round((float) $invoice->discount_amount, 2), $subtotal);
        $total = round($subtotal - $discount, 2);
        $paid = min(round((float) $invoice->payments()->sum('amount'), 2), $total);
        $balance = round($total - $paid, 2);
        $status = $balance <= 0.004 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        $invoice->forceFill([
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => max($balance, 0),
            'status' => $status,
        ])->save();

        return $invoice->fresh();
    }
}
