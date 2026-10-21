<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Court extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'sport', 'type', 'surface', 'enclosure', 'status', 'capacity',
        'lighting', 'accessible', 'description', 'hourly_price_cents',
        'two_hour_price_cents', 'three_plus_hour_price_cents',
    ];

    protected function casts(): array
    {
        return [
            'lighting' => 'boolean',
            'accessible' => 'boolean',
            'capacity' => 'integer',
            'version' => 'integer',
            'hourly_price_cents' => 'integer',
            'two_hour_price_cents' => 'integer',
            'three_plus_hour_price_cents' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hours(): HasMany
    {
        return $this->hasMany(CourtHour::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
