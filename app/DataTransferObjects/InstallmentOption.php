<?php

namespace App\DataTransferObjects;

readonly class InstallmentOption
{
    public function __construct(
        public int $number,
        public string $monthlyPrice,
        public string $totalPrice,
    ) {}

    public function label(): string
    {
        return $this->number === 1 ? 'Tek çekim' : "{$this->number} Taksit";
    }

    /**
     * @return array{number: int, label: string, monthly_price: string, total_price: string}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'label' => $this->label(),
            'monthly_price' => $this->monthlyPrice,
            'total_price' => $this->totalPrice,
        ];
    }
}
