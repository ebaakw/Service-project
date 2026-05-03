<?php

namespace App\Models;

use App\Enums\OrderOfferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'technician_id',
        'status',
        'offered_at',
        'responded_at',
        'expires_at',
        'response_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderOfferStatus::class,
            'offered_at' => 'datetime',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
