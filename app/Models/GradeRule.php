<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeRule extends Model
{
    protected $fillable = ['min_percentage', 'grade', 'grade_point', 'sort_order'];

    protected function casts(): array
    {
        return ['min_percentage' => 'decimal:2', 'grade_point' => 'decimal:2'];
    }
}
