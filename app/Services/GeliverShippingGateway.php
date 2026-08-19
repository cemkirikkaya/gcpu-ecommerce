<?php

namespace App\Services;

use App\Contracts\ShippingGateway;
use App\DataTransferObjects\ShipmentCreationResult;
use App\Models\Order;
use Geliver\ApiException;
use Geliver\Client;
use RuntimeException;

class GeliverShippingGateway implements ShippingGateway
{
    public function __construct(
        private ?Client $client = null,
    ) {}

    public function createShipment(Order $order): ShipmentCreationResult
    {
        $order->loadMissing(['address', 'cart.user']);

        $senderAddressId = config('geliver.sender_address_id');

        if (blank($senderAddressId)) {
            throw new RuntimeException('Geliver gönderici adresi yapılandırılmamış.');
        }

        if ($order->address === null) {
            throw new RuntimeException('Sipariş teslimat adresi bulunamadı.');
        }

        $parcel = config('geliver.default_parcel');
        $payload = [
            'senderAddressID' => $senderAddressId,
            'recipientAddress' => $this->recipientAddressFromOrder($order),
            'length' => (string) $parcel['length'],
            'width' => (string) $parcel['width'],
            'height' => (string) $parcel['height'],
            'distanceUnit' => 'cm',
            'weight' => (string) $parcel['weight'],
            'massUnit' => 'kg',
            'order' => [
                'orderNumber' => (string) $order->id,
                'sourceIdentifier' => rtrim((string) config('app.frontend_url'), '/'),
                'totalAmount' => (string) $order->total_price,
                'totalAmountCurrency' => 'TRY',
            ],
        ];

        $client = $this->client();

        try {
            $shipment = $this->unwrapResponse(config('geliver.test')
                ? $client->shipments()->createTest($payload)
                : $client->shipments()->create($payload));

            $shipmentId = (string) ($shipment['id'] ?? '');

            if ($shipmentId === '') {
                throw new RuntimeException('Geliver gönderi oluşturulamadı.');
            }

            $cheapestOffer = $this->waitForCheapestOffer($client, $shipmentId, $shipment);
            $transaction = $this->unwrapResponse($client->transactions()->acceptOffer($cheapestOffer['id']));

            $transactionShipment = $transaction['shipment'] ?? [];

            return new ShipmentCreationResult(
                shipmentId: $shipmentId,
                trackingNumber: $transactionShipment['trackingNumber'] ?? $shipment['trackingNumber'] ?? null,
                trackingUrl: $transactionShipment['trackingUrl'] ?? $shipment['trackingUrl'] ?? null,
                labelUrl: $transactionShipment['labelURL'] ?? null,
            );
        } catch (ApiException $exception) {
            throw new RuntimeException(
                trim($exception->getMessage().' '.($exception->additionalMessage ?? '')),
                $exception->status,
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $initialShipment
     * @return array<string, mixed>
     */
    private function waitForCheapestOffer(Client $client, string $shipmentId, array $initialShipment, int $maxAttempts = 30): array
    {
        $shipment = $initialShipment;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $cheapestOffer = $shipment['offers']['cheapest'] ?? null;

            if (is_array($cheapestOffer) && ! empty($cheapestOffer['id'])) {
                return $cheapestOffer;
            }

            sleep(1);
            $shipment = $this->unwrapResponse($client->shipments()->get($shipmentId));
        }

        throw new RuntimeException('Geliver teklifleri hazır değil.');
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function unwrapResponse(array $response): array
    {
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function recipientAddressFromOrder(Order $order): array
    {
        $address = $order->address;
        $customer = $order->user();

        $country = strtoupper(trim((string) $address->country));

        if ($country === '' || strlen($country) !== 2) {
            $country = 'TR';
        }

        return [
            'name' => $address->fullName(),
            'email' => $customer?->email ?? 'customer@example.com',
            'phone' => $address->phone ?? '+905000000000',
            'address1' => trim($address->address_line_1.' '.($address->address_line_2 ?? '')),
            'countryCode' => $country,
            'cityName' => $address->city,
            'districtName' => $address->state ?: $address->city,
            'zip' => $address->postal_code ?: '34000',
        ];
    }

    private function client(): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        $token = config('geliver.api_token');

        if (blank($token)) {
            throw new RuntimeException('Geliver API token yapılandırılmamış.');
        }

        return new Client($token);
    }
}
