<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use InvalidArgumentException;
use RuntimeException;

class OrderShipmentService
{
    public function __construct(
        private ShippingGatewayFactory $shippingGatewayFactory,
        private AdminOrderService $adminOrderService,
        private GeliverTrackingStatusMapper $trackingStatusMapper,
        private GeliverEstimatedDeliveryResolver $estimatedDeliveryResolver,
        private GeliverTrackingUrlResolver $trackingUrlResolver,
    ) {}

    public function createShipment(Order $order): Order
    {
        $order->loadMissing(['address', 'cart.user']);

        if ($order->payment_status !== PaymentStatus::Paid) {
            throw new InvalidArgumentException('Ödenmemiş sipariş için kargo oluşturulamaz.');
        }

        if ($order->geliver_shipment_id !== null) {
            throw new InvalidArgumentException('Bu sipariş için kargo zaten oluşturulmuş.');
        }

        if (! in_array($order->status, [OrderStatus::Processing, OrderStatus::Pending], true)) {
            throw new InvalidArgumentException('Kargo yalnızca hazırlanan siparişler için oluşturulabilir.');
        }

        if ($order->address === null) {
            throw new InvalidArgumentException('Sipariş teslimat adresi bulunamadı.');
        }

        if (blank($order->address->phone)) {
            throw new InvalidArgumentException('Teslimat adresinde telefon numarası zorunludur.');
        }

        try {
            $result = $this->shippingGatewayFactory->make()->createShipment($order);
        } catch (RuntimeException $exception) {
            throw new InvalidArgumentException($exception->getMessage(), previous: $exception);
        }

        $order->update([
            'geliver_shipment_id' => $result->shipmentId,
            'tracking_number' => $result->trackingNumber,
            'tracking_url' => $this->trackingUrlResolver->resolve($result->trackingUrl, $result->shipmentId),
            'estimated_delivery_at' => $result->estimatedDeliveryAt,
        ]);

        $order = $order->fresh();

        if ($order->status === OrderStatus::Pending) {
            $order = $this->adminOrderService->updateStatus($order, OrderStatus::Processing);
        }

        if ($order->status === OrderStatus::Processing) {
            $order = $this->adminOrderService->updateStatus($order, OrderStatus::Shipped);
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $shipmentData
     */
    public function syncFromWebhook(Order $order, array $shipmentData): Order
    {
        $this->syncTrackingFromWebhook(
            $order,
            isset($shipmentData['trackingNumber']) ? (string) $shipmentData['trackingNumber'] : null,
            isset($shipmentData['trackingUrl']) ? (string) $shipmentData['trackingUrl'] : null,
            isset($shipmentData['id']) ? (string) $shipmentData['id'] : null,
        );

        $this->syncEstimatedDeliveryFromWebhook($order, $shipmentData);

        if (! config('geliver.sync_status_from_webhook')) {
            return $order->fresh();
        }

        $targetStatus = $this->trackingStatusMapper->resolveOrderStatus($shipmentData);

        if ($targetStatus === null) {
            return $order->fresh();
        }

        $order = $order->fresh();

        if ($order->status->canTransitionTo($targetStatus)) {
            return $this->adminOrderService->updateStatus($order, $targetStatus);
        }

        if ($targetStatus === OrderStatus::Delivered && $order->status === OrderStatus::Delivered) {
            return $order;
        }

        return $order;
    }

    public function syncTrackingFromWebhook(
        Order $order,
        ?string $trackingNumber,
        ?string $trackingUrl,
        ?string $shipmentId = null,
    ): Order {
        $attributes = [];

        if (filled($trackingNumber)) {
            $attributes['tracking_number'] = $trackingNumber;
        }

        $resolvedTrackingUrl = $this->trackingUrlResolver->resolve(
            $trackingUrl,
            $shipmentId ?? $order->geliver_shipment_id,
        );

        if (filled($resolvedTrackingUrl)) {
            $attributes['tracking_url'] = $resolvedTrackingUrl;
        }

        if ($attributes !== []) {
            $order->update($attributes);
        }

        return $order->fresh();
    }

    /**
     * @param  array<string, mixed>  $shipmentData
     */
    public function syncEstimatedDeliveryFromWebhook(Order $order, array $shipmentData): Order
    {
        $estimatedDeliveryAt = $this->estimatedDeliveryResolver->resolve($shipmentData);

        if ($estimatedDeliveryAt === null) {
            return $order->fresh();
        }

        $order->update([
            'estimated_delivery_at' => $estimatedDeliveryAt,
        ]);

        return $order->fresh();
    }

    public function syncFromGeliverApi(Order $order): Order
    {
        if (blank($order->geliver_shipment_id)) {
            throw new InvalidArgumentException('Bu sipariş için Geliver gönderi kaydı bulunamadı.');
        }

        if (config('geliver.fake')) {
            throw new InvalidArgumentException('Sahte Geliver modunda API senkronu yapılamaz.');
        }

        /** @var GeliverShippingGateway $gateway */
        $gateway = app(GeliverShippingGateway::class);

        return $this->syncFromWebhook(
            $order,
            $gateway->fetchShipment((string) $order->geliver_shipment_id),
        );
    }

    public function syncPendingShipments(): int
    {
        if (config('geliver.fake') || ! config('geliver.auto_sync_from_api')) {
            return 0;
        }

        $syncedCount = 0;

        Order::query()
            ->whereNotNull('geliver_shipment_id')
            ->whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])
            ->orderBy('id')
            ->each(function (Order $order) use (&$syncedCount): void {
                try {
                    $this->syncFromGeliverApi($order);
                    $syncedCount++;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });

        return $syncedCount;
    }
}
