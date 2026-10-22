<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'company_id', 'court_id', 'user_id', 'date', 'starts_at', 'ends_at',
        'duration_hours', 'price_cents', 'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'duration_hours' => 'integer',
            'price_cents' => 'integer',
        ];
    }

    public function scopeOccupying(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class)->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
