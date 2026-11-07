<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BidPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bid_quantity',
        'price',
        'badge',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bid_quantity' => 'integer',
            'price'        => 'decimal:2',
            'is_active'    => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
