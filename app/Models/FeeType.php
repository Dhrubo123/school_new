<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeType extends Model
{
    public const FREQUENCIES = ['monthly', 'term', 'annual', 'one_time'];

    protected $fillable = ['name', 'frequency', 'description', 'default_amount', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['default_amount' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
