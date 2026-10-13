<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentSection extends Model
{
    protected $fillable = ['page_slug', 'section_key', 'title', 'body', 'metadata', 'is_published', 'sort_order'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'is_published' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
