<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeInvoice extends Model
{
    public const STATUSES = ['unpaid', 'partial', 'paid', 'void'];

    protected $fillable = ['student_profile_id', 'academic_year_id', 'invoice_no', 'title', 'billing_period', 'issued_on', 'due_date', 'subtotal_amount', 'discount_amount', 'total_amount', 'paid_amount', 'balance_amount', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'due_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }
}
