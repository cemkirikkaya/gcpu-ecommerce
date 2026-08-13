<?php

namespace App\Http\Resources\Api;

use App\Models\OrderCancellationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderCancellationRequest */
class OrderCancellationRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'message' => $this->message,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'admin_note' => $this->admin_note,
            'refund_reference' => $this->refund_reference,
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'customer' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}
