<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Services\GeliverTrackingUrlResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'address_id',
        'total_price',
        'status',
        'payment_status',
        'iyzico_token',
        'iyzico_payment_id',
        'iyzico_payment_items',
        'installment',
        'paid_price',
        'iyzico_conversation_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paid_at',
        'tracking_number',
        'tracking_url',
        'geliver_shipment_id',
        'estimated_delivery_at',
    ];

    protected $attributes = [
        'status' => OrderStatus::Pending->value,
        'payment_status' => PaymentStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'paid_price' => 'decimal:2',
            'installment' => 'integer',
            'iyzico_payment_items' => 'array',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
        ];
    }

    public function paymentProvider(): ?string
    {
        if ($this->stripe_checkout_session_id !== null) {
            return 'stripe';
        }

        if ($this->iyzico_token !== null || $this->iyzico_payment_id !== null) {
            return 'iyzico';
        }

        return null;
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(OrderCancellationRequest::class);
    }

    public function latestCancellationRequest(): HasOne
    {
        return $this->hasOne(OrderCancellationRequest::class)->latestOfMany();
    }

    public function user(): ?User
    {
        return $this->cart?->user;
    }

    public function trackingPageUrl(): ?string
    {
        return app(GeliverTrackingUrlResolver::class)->resolve(
            $this->tracking_url,
            $this->geliver_shipment_id,
        );
    }
}
