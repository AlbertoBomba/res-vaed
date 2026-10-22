<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtBlock extends Model
{
    protected $fillable = ['company_id', 'court_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class)->withTrashed();
    }
}
