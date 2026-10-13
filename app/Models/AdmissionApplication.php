<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplication extends Model
{
    public const STATUSES = ['new', 'contacted', 'reviewing', 'accepted', 'declined'];

    protected $fillable = ['application_no', 'student_name', 'guardian_name', 'email', 'phone', 'applying_for_class', 'message', 'status', 'review_notes', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
