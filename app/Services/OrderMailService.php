<?php

namespace App\Services;

use App\Mail\OrderCancellationApprovedMail;
use App\Mail\OrderCancellationRejectedMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderDeliveredMail;
use App\Mail\OrderReturnApprovedMail;
use App\Mail\OrderReturnCompletedMail;
use App\Mail\OrderReturnRejectedMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class OrderMailService
{
    /**
     * @param  list<string>  $relations
     */
    public function queueConfirmation(Order $order, array $relations = []): void
    {
        $this->queueOrderMail($order, new OrderConfirmationMail($order), $relations);
    }

    /**
     * @param  list<string>  $relations
     */
    public function queueShipped(Order $order, array $relations = []): void
    {
        $this->queueOrderMail($order, new OrderShippedMail($order), $relations);
    }

    /**
     * @param  list<string>  $relations
     */
    public function queueDelivered(Order $order, array $relations = []): void
    {
        $this->queueOrderMail($order, new OrderDeliveredMail($order), $relations);
    }

    public function queueCancellationApproved(OrderCancellationRequest $cancellationRequest): void
    {
        $this->queueCancellationMail(
            $cancellationRequest,
            new OrderCancellationApprovedMail($cancellationRequest),
        );
    }

    public function queueCancellationRejected(OrderCancellationRequest $cancellationRequest): void
    {
        $this->queueCancellationMail(
            $cancellationRequest,
            new OrderCancellationRejectedMail($cancellationRequest),
        );
    }

    public function queueReturnApproved(OrderReturnRequest $returnRequest): void
    {
        $this->queueReturnMail($returnRequest, new OrderReturnApprovedMail($returnRequest));
    }

    public function queueReturnRejected(OrderReturnRequest $returnRequest): void
    {
        $this->queueReturnMail($returnRequest, new OrderReturnRejectedMail($returnRequest));
    }

    public function queueReturnCompleted(OrderReturnRequest $returnRequest): void
    {
        $this->queueReturnMail($returnRequest, new OrderReturnCompletedMail($returnRequest));
    }

    /**
     * @param  list<string>  $relations
     */
    private function queueOrderMail(Order $order, Mailable $mailable, array $relations = []): void
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product',
            'address',
            'cart.user',
            ...$relations,
        ]);

        $customer = $order->user();

        if ($customer === null || $customer->email === null) {
            return;
        }

        Mail::to($customer)->send($mailable);
    }

    private function queueCancellationMail(
        OrderCancellationRequest $cancellationRequest,
        Mailable $mailable,
    ): void {
        $cancellationRequest->loadMissing([
            'order.items.cartItem.productVariant.product',
            'order.address',
            'order.cart.user',
            'user',
        ]);

        $customer = $cancellationRequest->user;

        if ($customer->email === null) {
            return;
        }

        Mail::to($customer)->send($mailable);
    }

    private function queueReturnMail(OrderReturnRequest $returnRequest, Mailable $mailable): void
    {
        $returnRequest->loadMissing([
            'order.items.cartItem.productVariant.product',
            'order.address',
            'order.cart.user',
            'items.orderItem.cartItem.productVariant.product',
            'user',
        ]);

        $customer = $returnRequest->user;

        if ($customer->email === null) {
            return;
        }

        Mail::to($customer)->send($mailable);
    }
}
