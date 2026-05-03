<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @use HasFactory<PaymentFactory>
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (empty($payment->ulid)) {
                $payment->ulid = (string) Str::ulid();
            }
        });
    }

    protected $fillable = [
        'ulid',
        'order_id',
        'invoice_id',
        'customer_id',
        'company_id',
        'payment_type',
        'payment_status',
        'provider',
        'provider_reference',
        'amount',
        'currency',
        'payment_url',
        'expires_at',
        'paid_at',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'payment_status' => PaymentStatus::class,
            'amount' => 'decimal:3',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }
}
