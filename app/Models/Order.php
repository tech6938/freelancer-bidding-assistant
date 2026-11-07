<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_type',
        'item_id',
        'item_name',
        'amount',
        'bid_quantity_added',
        'payment_method',
        'bank_card_id',
        'screenshot_path',
        'sender_name',
        'sender_note',
        'card_holder_name',
        'card_brand',
        'card_last_four',
        'card_exp_month',
        'card_exp_year',
        'status',
        'transaction_ref',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'bid_quantity_added' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankCard(): BelongsTo
    {
        return $this->belongsTo(BankCard::class)->withTrashed();
    }

    public function getScreenshotUrlAttribute(): ?string
    {
        if ($this->screenshot_path && Storage::disk('public')->exists($this->screenshot_path)) {
            return asset('storage/' . $this->screenshot_path);
        }
        return null;
    }
}
