<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DataTransferObjects\PaymentInitializationResult;
use App\DataTransferObjects\PaymentRetrievalResult;
use App\Models\Order;
use App\Support\IyzicoBuyerData;
use Illuminate\Support\Facades\Log;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\Payment;
use Iyzipay\Model\PaymentCard;
use Iyzipay\Model\PaymentChannel;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\CreatePaymentRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
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

        $basketItems = [];

        foreach ($order->items as $index => $item) {
            $variant = $item->cartItem?->productVariant;
            $product = $variant?->product;
            $linePrice = $this->formatAmount($item->subtotal());

            $basketItem = new BasketItem;
            $basketItem->setId('item-'.$item->id);
            $basketItem->setName($product?->name ?? 'Ürün');
            $basketItem->setCategory1($product?->category?->name ?? 'Genel');
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($linePrice);
            $basketItems[$index] = $basketItem;
        }

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

    public function chargeDirectly(Order $order, string $buyerIp): PaymentRetrievalResult
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

        $request = new CreatePaymentRequest;
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setPrice($price);
        $request->setPaidPrice($price);
        $request->setCurrency(Currency::TL);
        $request->setInstallment(1);
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

        $basketItems = [];

        foreach ($order->items as $index => $item) {
            $variant = $item->cartItem?->productVariant;
            $product = $variant?->product;
            $linePrice = $this->formatAmount($item->subtotal());

            $basketItem = new BasketItem;
            $basketItem->setId('item-'.$item->id);
            $basketItem->setName($product?->name ?? 'Ürün');
            $basketItem->setCategory1($product?->category?->name ?? 'Genel');
            $basketItem->setItemType(BasketItemType::PHYSICAL);
            $basketItem->setPrice($linePrice);
            $basketItems[$index] = $basketItem;
        }

        $request->setBasketItems($basketItems);

        Log::info('iyzico direct payment request sending', [
            'order_id' => $order->id,
            'conversation_id' => $conversationId,
            'amount' => $price,
            'card_last_four' => substr((string) $cardNumber, -4),
        ]);

        $response = Payment::create($request, $this->options());

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
        );
    }

    private function directPaymentSuccessful(Payment $response): bool
    {
        if ($response->getPaymentId() === null) {
            return false;
        }

        $paymentStatus = $response->getPaymentStatus();

        return $paymentStatus === null || $paymentStatus === 'SUCCESS';
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
        );
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
