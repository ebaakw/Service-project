<?php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'label',
        'contact_name',
        'contact_phone',
        'area',
        'block',
        'street',
        'building',
        'floor',
        'apartment',
        'full_address',
        'lat',
        'lng',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (CustomerAddress $address): void {
            if (! $address->is_default) {
                return;
            }

            $customer = Customer::query()->find($address->customer_id);
            if ($customer === null) {
                return;
            }

            CustomerAddress::query()
                ->where('customer_id', $customer->getKey())
                ->whereKeyNot($address->getKey())
                ->update(['is_default' => false]);

            if ($customer->default_address_id !== $address->getKey()) {
                $customer->forceFill(['default_address_id' => $address->getKey()])->saveQuietly();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
