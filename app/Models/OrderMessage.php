<?php

namespace App\Models;

use App\Enums\OrderMessageActorContext;
use App\Enums\OrderMessageSenderRole;
use App\Enums\OrderMessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    protected $fillable = [
        'order_id',
        'sender_user_id',
        'actor_user_id',
        'actor_context',
        'sender_role',
        'type',
        'body',
        'proposed_scheduled_at',
        'proposed_duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'sender_role' => OrderMessageSenderRole::class,
            'actor_context' => OrderMessageActorContext::class,
            'type' => OrderMessageType::class,
            'proposed_scheduled_at' => 'datetime',
            'proposed_duration_minutes' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function onBehalfLabel(): ?string
    {
        return $this->actor_context?->onBehalfLabel();
    }
}
