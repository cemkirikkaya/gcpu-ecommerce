<?php

namespace App\Models;

use App\Enums\CancellationRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancellationRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'message',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'refund_reference',
    ];

    protected $attributes = [
        'status' => CancellationRequestStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CancellationRequestStatus::class,
            'reviewed_at' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === CancellationRequestStatus::Pending;
    }
}
