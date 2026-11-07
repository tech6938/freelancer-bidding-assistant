<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class BankCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_name',
        'account_title',
        'logo_image',
        'card_number',
        'iban',
        'branch_code',
        'instructions',
        'qr_code_image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        $img = $this->logo_image ?: $this->qr_code_image;
        if ($img && Storage::disk('public')->exists($img)) {
            return asset('storage/' . $img);
        }
        return null;
    }
}
