<?php

use App\Support\IyzicoBuyerData;

it('maps test domain emails to an iyzico compatible address', function () {
    expect(IyzicoBuyerData::email('user@blog.test', 5))->toBe('buyer5@example.com');
});

it('keeps regular emails unchanged', function () {
    expect(IyzicoBuyerData::email('Shopper@Example.com', 5))->toBe('shopper@example.com');
});

it('normalizes turkish phone numbers', function () {
    expect(IyzicoBuyerData::gsm('0532 123 45 67'))->toBe('+905321234567');
});
