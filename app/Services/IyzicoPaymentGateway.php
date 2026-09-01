<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\InstallmentOption;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRefundResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;
use App\Support\IyzicoBuyerData;
use App\Support\OrderPaymentLineAmounts;
use Illuminate\Support\Facades\Log;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Currency;
use Iyzipay\Model\InstallmentInfo;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Payment;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\PaymentChannel;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\PaymentResource;
use Iyzipay\Model\Refund;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Request\CreateRefundRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Iyzipay\Request\RetrieveInstallmentInfoRequest;
use Iyzipay\Request\RetrievePaymentRequest;
use RuntimeException;

class IyzicoPaymentGateway implements PaymentGateway
{
    public function initialize(Order $order, string $buyerIp): PaymentInitializationResult
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product.category',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
            'address',
            'cart.user',
        ]);

        $user = $order->cart?->user;
        $address = $order->address;

        if ($user === null || $address === null) {
            throw new RuntimeException('Ödeme için sipariş bilgileri eksik.');
        }

        $conversationId = $this->conversationId($order);
        $price = $this->formatAmount((float) $order->total_price);

        $request = new CreateCheckoutFormInitializeRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency(Currency::TL);
        $request->setBasketId($conversationId);
        $request->setPaymentGroup(PaymentGroup::PRODUCT);
        $request->setCallbackUrl((string) config('iyzico.callback_url'));

        $buyer = new Buyer;
        $buyer->setId((string) $user->id);
        $buyer->setName($address->first_name);
        $buyer->setSurname($address->last_name);
        $buyer->setGsmNumber(IyzicoBuyerData::gsm($address->phone));
        $buyer->setEmail(IyzicoBuyerData::email($user->email, $user->id));
        $buyer->setIdentityNumber('74300864791');
        $buyer->setRegistrationAddress($address->address_line_1);
        $buyer->setIp($buyerIp);
        $buyer->setCity($address->city);
        $buyer->setCountry($this->normalizeCountry($address->country));
        $buyer->setZipCode($address->postal_code);
        $request->setBuyer($buyer);

        $iyzicoAddress = new Address;
        $iyzicoAddress->setContactName($address->fullName());
        $iyzicoAddress->setCity($address->city);
        $iyzicoAddress->setCountry($this->normalizeCountry($address->country));
        $iyzicoAddress->setAddress($address->address_line_1);
        $iyzicoAddress->setZipCode($address->postal_code);
        $request->setShippingAddress($iyzicoAddress);
        $request->setBillingAddress($iyzicoAddress);

        $basketItems = $this->buildBasketItems($order);

        $request->setBasketItems($basketItems);

        $response = CheckoutFormInitialize::create($request, $this->options());

        if ($response->getStatus() !== 'success' || $response->getToken() === null) {
            Log::error('iyzico checkout initialize failed', [
                'order_id' => $order->id,
                'conversation_id' => $conversationId,
                'status' => $response->getStatus(),
                'error' => $response->getErrorMessage(),
            ]);

            throw new RuntimeException($response->getErrorMessage() ?: 'iyzico ödeme oturumu başlatılamadı.');
        }

        Log::info('iyzico checkout initialize succeeded', [
            'order_id' => $order->id,
            'conversation_id' => $conversationId,
            'token' => $response->getToken(),
        ]);

        return new PaymentInitializationResult(
            token: $response->getToken(),
            paymentPageUrl: (string) $response->getPaymentPageUrl(),
            conversationId: $conversationId,
        );
    }

    public function chargeDirectly(Order $order, string $buyerIp, int $installment = 1): PaymentRetrievalResult
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product.category',
            'items.cartItem.productVariant.variantValues.variantValue.variant',
            'address',
            'cart.user',
        ]);

        $user = $order->cart?->user;
        $address = $order->address;

        if ($user === null || $address === null) {
            throw new RuntimeException('Ödeme için sipariş bilgileri eksik.');
        }

        $conversationId = $this->conversationId($order);
        $price = $this->formatAmount((float) $order->total_price);
        $cardNumber = config('iyzico.test_card.number');

        if ($cardNumber === null || $cardNumber === '') {
            throw new RuntimeException('Doğrudan ödeme için test kart bilgileri tanımlı değil.');
        }

        $paidPrice = $this->resolvePaidPrice($price, (string) $cardNumber, $installment);

        $request = new CreatePaymentRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setPrice($price);
        $request->setPaidPrice($paidPrice);
        $request->setCurrency(Currency::TL);
        $request->setInstallment($installment);
        $request->setBasketId($conversationId);
        $request->setPaymentChannel(PaymentChannel::WEB);
        $request->setPaymentGroup(PaymentGroup::PRODUCT);

        $paymentCard = new PaymentCard;
        $paymentCard->setCardHolderName((string) config('iyzico.test_card.holder'));
        $paymentCard->setCardNumber((string) $cardNumber);
        $paymentCard->setExpireMonth((string) config('iyzico.test_card.expire_month'));
        $paymentCard->setExpireYear((string) config('iyzico.test_card.expire_year'));
        $paymentCard->setCvc((string) config('iyzico.test_card.cvc'));
        $paymentCard->setRegisterCard(0);
        $request->setPaymentCard($paymentCard);

        $buyer = new Buyer;
        $buyer->setId((string) $user->id);
        $buyer->setName($address->first_name);
        $buyer->setSurname($address->last_name);
        $buyer->setGsmNumber(IyzicoBuyerData::gsm($address->phone));
        $buyer->setEmail(IyzicoBuyerData::email($user->email, $user->id));
        $buyer->setIdentityNumber('74300864791');
        $buyer->setRegistrationAddress($address->address_line_1);
        $buyer->setIp($buyerIp);
        $buyer->setCity($address->city);
        $buyer->setCountry($this->normalizeCountry($address->country));
        $buyer->setZipCode($address->postal_code);
        $request->setBuyer($buyer);

        $iyzicoAddress = new Address;
        $iyzicoAddress->setContactName($address->fullName());
        $iyzicoAddress->setCity($address->city);
        $iyzicoAddress->setCountry($this->normalizeCountry($address->country));
        $iyzicoAddress->setAddress($address->address_line_1);
        $iyzicoAddress->setZipCode($address->postal_code);
        $request->setShippingAddress($iyzicoAddress);
        $request->setBillingAddress($iyzicoAddress);

        $basketItems = $this->buildBasketItems($order);

        $request->setBasketItems($basketItems);

        Log::info('iyzico direct payment request sending', [
            'order_id' => $order->id,
            'conversation_id' => $conversationId,
            'amount' => $price,
            'paid_price' => $paidPrice,
            'installment' => $installment,
            'card_last_four' => substr((string) $cardNumber, -4),
        ]);

        $response = Payment::create($request, $this->options());

        Log::info('iyzico direct payment full response', $this->paymentResponseContext($response, $order->id));

        if ($response->getStatus() !== 'success') {
            Log::warning('iyzico direct payment failed', [
                'order_id' => $order->id,
                'conversation_id' => $conversationId,
                'status' => $response->getStatus(),
                'payment_status' => $response->getPaymentStatus(),
                'error' => $response->getErrorMessage(),
            ]);

            return new PaymentRetrievalResult(
                successful: false,
                errorMessage: $response->getErrorMessage() ?: 'Ödeme tamamlanamadı.',
            );
        }

        Log::info('iyzico direct payment response received', [
            'order_id' => $order->id,
            'conversation_id' => $conversationId,
            'payment_id' => $response->getPaymentId(),
            'payment_status' => $response->getPaymentStatus(),
            'fraud_status' => $response->getFraudStatus(),
        ]);

        $successful = $this->directPaymentSuccessful($response);

        if (! $successful) {
            Log::warning('iyzico direct payment rejected', [
                'order_id' => $order->id,
                'conversation_id' => $conversationId,
                'payment_id' => $response->getPaymentId(),
                'payment_status' => $response->getPaymentStatus(),
                'error' => $response->getErrorMessage(),
            ]);
        }

        return new PaymentRetrievalResult(
            successful: $successful,
            paymentId: $response->getPaymentId(),
            errorMessage: $successful
                ? null
                : ($response->getErrorMessage() ?: 'Ödeme tamamlanamadı.'),
            installment: (int) ($response->getInstallment() ?? $installment),
            paidPrice: $response->getPaidPrice() !== null
                ? $this->formatAmount((float) $response->getPaidPrice())
                : $paidPrice,
            iyzicoPaymentItems: $successful ? $this->extractPaymentItems($response) : [],
        );
    }

    private function resolvePaidPrice(string $price, string $cardNumber, int $installment): string
    {
        $options = $this->getInstallmentOptions($price, substr($cardNumber, 0, 6));

        foreach ($options as $option) {
            if ($option->number === $installment) {
                return $option->totalPrice;
            }
        }

        return $price;
    }

    private function directPaymentSuccessful(Payment $response): bool
    {
        if ($response->getPaymentId() === null) {
            return false;
        }

        $paymentStatus = $response->getPaymentStatus();

        return $paymentStatus === null || $paymentStatus === 'SUCCESS';
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentResponseContext(Payment $response, int $orderId): array
    {
        return [
            'order_id' => $orderId,
            'status' => $response->getStatus(),
            'error_code' => $response->getErrorCode(),
            'error_message' => $response->getErrorMessage(),
            'error_group' => $response->getErrorGroup(),
            'conversation_id' => $response->getConversationId(),
            'payment_id' => $response->getPaymentId(),
            'payment_status' => $response->getPaymentStatus(),
            'fraud_status' => $response->getFraudStatus(),
            'price' => $response->getPrice(),
            'paid_price' => $response->getPaidPrice(),
            'currency' => $response->getCurrency(),
            'installment' => $response->getInstallment(),
            'auth_code' => $response->getAuthCode(),
            'bin_number' => $response->getBinNumber(),
            'last_four_digits' => $response->getLastFourDigits(),
            'card_type' => $response->getCardType(),
            'card_association' => $response->getCardAssociation(),
            'card_family' => $response->getCardFamily(),
            'basket_id' => $response->getBasketId(),
            'phase' => $response->getPhase(),
        ];
    }

    public function retrieve(string $token): PaymentRetrievalResult
    {
        $request = new RetrieveCheckoutFormRequest;
        $request->setLocale(Locale::TR);
        $request->setToken($token);

        $response = CheckoutForm::retrieve($request, $this->options());

        if ($response->getStatus() !== 'success') {
            Log::warning('iyzico payment retrieve failed', [
                'token' => $token,
                'status' => $response->getStatus(),
                'error' => $response->getErrorMessage(),
            ]);

            return new PaymentRetrievalResult(
                successful: false,
                errorMessage: $response->getErrorMessage() ?: 'Ödeme sonucu alınamadı.',
            );
        }

        Log::info('iyzico payment retrieve succeeded', [
            'token' => $token,
            'payment_id' => $response->getPaymentId(),
            'payment_status' => $response->getPaymentStatus(),
        ]);

        return new PaymentRetrievalResult(
            successful: $response->getPaymentStatus() === 'SUCCESS',
            paymentId: $response->getPaymentId(),
            errorMessage: $response->getPaymentStatus() === 'SUCCESS'
                ? null
                : ($response->getErrorMessage() ?: 'Ödeme tamamlanamadı.'),
            installment: $response->getInstallment() !== null
                ? (int) $response->getInstallment()
                : null,
            paidPrice: $response->getPaidPrice() !== null
                ? $this->formatAmount((float) $response->getPaidPrice())
                : null,
            iyzicoPaymentItems: $response->getPaymentStatus() === 'SUCCESS'
                ? $this->extractPaymentItems($response)
                : [],
        );
    }

    /**
     * @return list<InstallmentOption>
     */
    public function getInstallmentOptions(string $price, string $binNumber): array
    {
        $request = new RetrieveInstallmentInfoRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId('installment-'.uniqid());
        $request->setBinNumber(substr($binNumber, 0, 6));
        $request->setPrice($price);

        $response = InstallmentInfo::retrieve($request, $this->options());

        if ($response->getStatus() !== 'success') {
            throw new RuntimeException($response->getErrorMessage() ?: 'Taksit seçenekleri alınamadı.');
        }

        $details = $response->getInstallmentDetails() ?? [];

        if ($details === []) {
            return [new InstallmentOption(
                number: 1,
                monthlyPrice: $price,
                totalPrice: $price,
            )];
        }

        $options = [];

        foreach ($details as $detail) {
            foreach ($detail->getInstallmentPrices() ?? [] as $installmentPrice) {
                $options[] = new InstallmentOption(
                    number: (int) $installmentPrice->getInstallmentNumber(),
                    monthlyPrice: (string) $installmentPrice->getInstallmentPrice(),
                    totalPrice: (string) $installmentPrice->getTotalPrice(),
                );
            }

            break;
        }

        usort($options, fn (InstallmentOption $a, InstallmentOption $b): int => $a->number <=> $b->number);

        return $options;
    }

    public function refund(Order $order, ?float $amount = null): PaymentRefundResult
    {
        $items = $this->resolveRefundItems($order);

        if ($amount !== null) {
            $items = $this->allocateRefundAmount($items, $amount);
        }

        if ($items === []) {
            return new PaymentRefundResult(
                successful: false,
                errorMessage: 'İade için ödeme kırılım kaydı bulunamadı.',
            );
        }

        $refundReferences = [];

        foreach ($items as $item) {
            $request = new CreateRefundRequest;
            $request->setLocale(Locale::TR);
            $request->setConversationId($this->conversationId($order));
            $request->setPaymentTransactionId($item['payment_transaction_id']);
            $request->setPrice($this->formatAmount((float) $item['price']));
            $request->setCurrency(Currency::TL);
            $request->setIp(request()->ip() ?? '127.0.0.1');

            $response = Refund::create($request, $this->options());

            if ($response->getStatus() !== 'success') {
                Log::warning('Iyzico refund failed', [
                    'order_id' => $order->id,
                    'payment_transaction_id' => $item['payment_transaction_id'],
                    'error' => $response->getErrorMessage(),
                ]);

                return new PaymentRefundResult(
                    successful: false,
                    errorMessage: $response->getErrorMessage() ?? 'İade işlemi başarısız.',
                );
            }

            $refundReferences[] = $response->getPaymentId() ?? $item['payment_transaction_id'];
        }

        return new PaymentRefundResult(
            successful: true,
            refundReference: implode(',', $refundReferences),
        );
    }

    /**
     * @param  list<array{payment_transaction_id: string, price: string}>  $items
     * @return list<array{payment_transaction_id: string, price: string}>
     */
    private function allocateRefundAmount(array $items, float $amount): array
    {
        $remaining = round($amount, 2);
        $allocated = [];

        foreach ($items as $item) {
            if ($remaining <= 0) {
                break;
            }

            $itemPrice = round((float) $item['price'], 2);
            $refundPrice = min($itemPrice, $remaining);

            if ($refundPrice <= 0) {
                continue;
            }

            $allocated[] = [
                'payment_transaction_id' => $item['payment_transaction_id'],
                'price' => $this->formatAmount($refundPrice),
            ];

            $remaining = round($remaining - $refundPrice, 2);
        }

        return $allocated;
    }

    /**
     * @return list<array{payment_transaction_id: string, price: string}>
     */
    private function resolveRefundItems(Order $order): array
    {
        if (is_array($order->iyzico_payment_items) && $order->iyzico_payment_items !== []) {
            return $order->iyzico_payment_items;
        }

        if ($order->iyzico_payment_id === null) {
            return [];
        }

        $request = new RetrievePaymentRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId($this->conversationId($order));
        $request->setPaymentId($order->iyzico_payment_id);
        $request->setPaymentConversationId(
            $order->iyzico_conversation_id ?? $this->conversationId($order),
        );

        $payment = Payment::retrieve($request, $this->options());

        if ($payment->getStatus() !== 'success') {
            Log::warning('Iyzico payment retrieve for refund failed', [
                'order_id' => $order->id,
                'payment_id' => $order->iyzico_payment_id,
                'error' => $payment->getErrorMessage(),
            ]);

            return [];
        }

        return $this->extractPaymentItems($payment);
    }

    /**
     * @return list<array{payment_transaction_id: string, price: string}>
     */
    private function extractPaymentItems(PaymentResource $response): array
    {
        $items = [];

        foreach ($response->getPaymentItems() ?? [] as $paymentItem) {
            $transactionId = $paymentItem->getPaymentTransactionId();

            if ($transactionId === null || $transactionId === '') {
                continue;
            }

            $price = $paymentItem->getPaidPrice() ?? $paymentItem->getPrice();

            $items[] = [
                'payment_transaction_id' => (string) $transactionId,
                'price' => $this->formatAmount((float) $price),
            ];
        }

        return $items;
    }

    /**
     * @return list<BasketItem>
     */
    private function buildBasketItems(Order $order): array
    {
        $order->loadMissing([
            'items.cartItem.productVariant.product.category',
        ]);

        $lineAmounts = OrderPaymentLineAmounts::forOrder($order);
        $basketItems = [];

        foreach ($order->items->values() as $index => $item) {
            $variant = $item->cartItem?->productVariant;
            $product = $variant?->product;
            $linePrice = $this->formatAmount($lineAmounts[$index] ?? $item->subtotal());

            $basketItem = new BasketItem;
            $basketItem->setId('item-'.$item->id);
            $basketItem->setName($product?->name ?? 'Ürün');
            $basketItem->setCategory1($product?->category?->name ?? 'Genel');
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($linePrice);
            $basketItems[] = $basketItem;
        }

        return $basketItems;
    }

    private function options(): Options
    {
        $options = new Options;
        $options->setApiKey((string) config('iyzico.api_key'));
        $options->setSecretKey((string) config('iyzico.secret_key'));
        $options->setBaseUrl((string) config('iyzico.base_url'));

        return $options;
    }

    private function conversationId(Order $order): string
    {
        return 'order-'.$order->id;
    }

    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function normalizeCountry(string $country): string
    {
        return match (mb_strtolower($country)) {
            'türkiye', 'turkiye', 'tr' => 'Turkey',
            default => $country,
        };
    }
}
