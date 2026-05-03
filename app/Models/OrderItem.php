<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @use HasFactory<OrderItemFactory>
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, HasLocalizedFields, SoftDeletes;

    protected $fillable = [
        'order_id',
        'company_price_item_id',
        'name',
        'name_ar',
        'name_en',
        'description',
        'description_ar',
        'description_en',
        'quantity',
        'unit_price',
        'total_price',
        'warranty_days',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item): void {
            $item->name = $item->name_ar ?: $item->name_en ?: $item->name;
            $item->description = $item->description_ar ?: $item->description_en ?: $item->description;
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:3',
            'total_price' => 'decimal:3',
            'warranty_days' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function priceItem(): BelongsTo
    {
        return $this->belongsTo(CompanyPriceItem::class, 'company_price_item_id');
    }

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }
}
