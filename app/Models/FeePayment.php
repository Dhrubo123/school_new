<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    public const METHODS = ['cash', 'bank_transfer', 'mobile_banking', 'card', 'other'];

    protected $fillable = ['fee_invoice_id', 'received_by', 'receipt_no', 'amount', 'payment_date', 'method', 'reference', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'fee_invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
