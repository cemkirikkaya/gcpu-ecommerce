<?php

namespace App\Services;

class GeliverTrackingUrlResolver
{
    public function resolve(?string $trackingUrl, ?string $shipmentId): ?string
    {
        if ($this->isUsableTrackingUrl($trackingUrl)) {
            return $trackingUrl;
        }

        if (blank($shipmentId)) {
            return null;
        }

        return rtrim((string) config('geliver.tracking_page_base'), '/').'/'.trim($shipmentId);
    }

    private function isUsableTrackingUrl(?string $trackingUrl): bool
    {
        if (blank($trackingUrl)) {
            return false;
        }

        $host = parse_url($trackingUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        return ! in_array(strtolower($host), ['example.com', 'www.example.com'], true);
    }
}
