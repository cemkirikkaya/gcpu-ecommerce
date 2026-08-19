<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\OrderShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class CreateOrderShipmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [30, 120, 300];

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function handle(OrderShipmentService $orderShipmentService): void
    {
        if (! config('geliver.auto_create_on_payment')) {
            return;
        }

        $order = $this->order->fresh(['address', 'cart.user']);

        if ($order === null) {
            return;
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            return;
        }

        if ($order->geliver_shipment_id !== null) {
            return;
        }

        try {
            $orderShipmentService->createShipment($order);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Automatic Geliver shipment creation skipped', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Automatic Geliver shipment creation failed', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
