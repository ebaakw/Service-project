<?php

namespace App\Models;

use App\Enums\FeedbackPriority;
use App\Enums\FeedbackTicketStatus;
use App\Enums\FeedbackTicketType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FeedbackTicket extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackTicketFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ulid',
        'customer_id',
        'company_id',
        'order_id',
        'type',
        'subject',
        'message',
        'status',
        'priority',
        'assigned_to_user_id',
        'resolved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeedbackTicket $ticket): void {
            if (empty($ticket->ulid)) {
                $ticket->ulid = (string) Str::ulid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => FeedbackTicketType::class,
            'status' => FeedbackTicketStatus::class,
            'priority' => FeedbackPriority::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(FeedbackReply::class, 'feedback_ticket_id')->orderBy('id');
    }

    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal', false);
    }
}
