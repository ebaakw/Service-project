<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIntakeAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_intake_field_id',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function intakeField(): BelongsTo
    {
        return $this->belongsTo(ServiceIntakeField::class, 'service_intake_field_id');
    }
}
