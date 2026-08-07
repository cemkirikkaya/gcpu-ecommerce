<?php

namespace App\Support;

class PaymentProviderCatalog
{
    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     supports_direct: bool,
     *     supports_installments: bool
     * }>
     */
    public static function available(): array
    {
        /** @var array<string, array{label: string, enabled: bool, supports_direct: bool, supports_installments: bool}> $providers */
        $providers = config('payments.providers', []);

        return collect($providers)
            ->map(function (array $provider, string $id): array {
                return [
                    'id' => $id,
                    'label' => $provider['label'],
                    'supports_direct' => $provider['supports_direct'],
                    'supports_installments' => $provider['supports_installments'],
                ];
            })
            ->filter(function (array $provider): bool {
                return (bool) config('payments.providers.'.$provider['id'].'.enabled');
            })
            ->values()
            ->all();
    }
}
