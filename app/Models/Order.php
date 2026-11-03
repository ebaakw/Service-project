<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->ulid)) {
                $order->ulid = (string) Str::ulid();
            }
        });
    }

    protected $fillable = [
        'ulid',
        'order_number',
        'company_id',
        'customer_id',
        'customer_address_id',
        'technician_id',
        'service_category_id',
        'service_problem_id',
        'custom_problem_text',
        'status',
        'customer_notes',
        'inspection_fee',
        'inspection_payment_status',
        'final_payment_status',
        'subtotal',
        'discount_amount',
        'total_amount',
        'scheduled_at',
        'inspection_paid_at',
        'assigned_at',
        'accept_sla_breached_at',
        'accepted_at',
        'technician_on_way_at',
        'arrived_at',
        'arrival_sla_breached_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'inspection_payment_status' => PaymentStatus::class,
            'final_payment_status' => PaymentStatus::class,
            'inspection_fee' => 'decimal:3',
            'subtotal' => 'decimal:3',
            'discount_amount' => 'decimal:3',
            'total_amount' => 'decimal:3',
            'scheduled_at' => 'datetime',
            'inspection_paid_at' => 'datetime',
            'assigned_at' => 'datetime',
            'accept_sla_breached_at' => 'datetime',
            'accepted_at' => 'datetime',
            'technician_on_way_at' => 'datetime',
            'arrived_at' => 'datetime',
            'arrival_sla_breached_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function serviceProblem(): BelongsTo
    {
        return $this->belongsTo(ServiceProblem::class);
    }

    public function intakeAnswers(): HasMany
    {
        return $this->hasMany(OrderIntakeAnswer::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(OrderOffer::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function feedbackTickets(): HasMany
    {
        return $this->hasMany(FeedbackTicket::class);
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
