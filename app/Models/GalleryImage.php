<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImage extends Model
{
    protected $fillable = ['path', 'alt_text', 'sort_order'];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }
}
