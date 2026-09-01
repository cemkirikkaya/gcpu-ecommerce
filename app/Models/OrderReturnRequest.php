<?php

namespace App\Models;

use App\Enums\ReturnRequestStatus;
use App\Enums\ReturnRequestType;
use Database\Factories\OrderReturnRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturnRequest extends Model
{
    /** @use HasFactory<OrderReturnRequestFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'status',
        'message',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'refund_reference',
        'refund_amount',
        'geliver_return_shipment_id',
        'return_tracking_number',
        'return_tracking_url',
        'return_label_url',
        'geliver_exchange_shipment_id',
        'exchange_tracking_number',
        'exchange_tracking_url',
        'received_at',
        'completed_at',
    ];

    protected $attributes = [
        'type' => ReturnRequestType::Return->value,
        'status' => ReturnRequestStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReturnRequestType::class,
            'status' => ReturnRequestStatus::class,
            'refund_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class);
    }

    public function isPending(): bool
    {
        return $this->status === ReturnRequestStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === ReturnRequestStatus::Approved;
    }

    public function isReturn(): bool
    {
        return $this->type === ReturnRequestType::Return;
    }

    public function isExchange(): bool
    {
        return $this->type === ReturnRequestType::Exchange;
    }
}
