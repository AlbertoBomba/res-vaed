<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtHour extends Model
{
    public $timestamps = false;

    protected $fillable = ['weekday', 'hour'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'hour' => 'integer'];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
