<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Throwable;

class GeliverEstimatedDeliveryResolver
{
    /**
     * @param  array<string, mixed>  $shipmentData
     */
    public function resolve(array $shipmentData): ?Carbon
    {
        foreach ($this->candidateDateTimes($shipmentData) as $value) {
            $parsed = $this->parseDateTime($value);

            if ($parsed instanceof Carbon) {
                return $parsed;
            }
        }

        return $this->resolveFromPredictedHours($shipmentData);
    }

    /**
     * @param  array<string, mixed>  $shipmentData
     * @return list<mixed>
     */
    private function candidateDateTimes(array $shipmentData): array
    {
        $acceptedOffer = is_array($shipmentData['acceptedOffer'] ?? null)
            ? $shipmentData['acceptedOffer']
            : [];

        $cheapestOffer = is_array($shipmentData['offers']['cheapest'] ?? null)
            ? $shipmentData['offers']['cheapest']
            : [];

        return [
            $shipmentData['eta'] ?? null,
            $acceptedOffer['estimatedArrivalTime'] ?? null,
            $cheapestOffer['estimatedArrivalTime'] ?? null,
        ];
    }

    /**
     * Geliver returns predictedDeliveryTime as estimated hours until delivery.
     *
     * @param  array<string, mixed>  $shipmentData
     */
    private function resolveFromPredictedHours(array $shipmentData): ?Carbon
    {
        $acceptedOffer = is_array($shipmentData['acceptedOffer'] ?? null)
            ? $shipmentData['acceptedOffer']
            : [];

        $cheapestOffer = is_array($shipmentData['offers']['cheapest'] ?? null)
            ? $shipmentData['offers']['cheapest']
            : [];

        foreach ([$acceptedOffer, $cheapestOffer] as $offer) {
            $hours = $offer['predictedDeliveryTime'] ?? null;

            if (! is_numeric($hours)) {
                continue;
            }

            $baseTime = $this->parseDateTime($shipmentData['createdAt'] ?? null)
                ?? $this->parseDateTime($offer['createdAt'] ?? null);

            if ($baseTime === null) {
                continue;
            }

            return $baseTime->copy()->addSeconds((int) round((float) $hours * 3600))->utc();
        }

        return null;
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->utc();
        } catch (InvalidFormatException|Throwable) {
            return null;
        }
    }
}
