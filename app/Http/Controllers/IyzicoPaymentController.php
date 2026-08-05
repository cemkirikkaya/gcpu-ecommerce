<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IyzicoPaymentController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentGateway $paymentGateway,
    ) {}

    public function initialize(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        $initialization = $this->orderService->initializePayment(
            $order,
            request()->ip() ?? '127.0.0.1',
        );

        return redirect()->away($initialization->paymentPageUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $token = $request->string('token')->toString();

        Log::info('Payment callback received', [
            'token' => $token !== '' ? $token : null,
        ]);

        if ($token === '') {
            Log::warning('Payment callback missing token');

            return $this->redirectToFrontend(null, 'error');
        }

        $order = Order::query()->where('iyzico_token', $token)->first();

        if ($order === null) {
            Log::warning('Payment callback order not found', [
                'token' => $token,
            ]);

            return $this->redirectToFrontend(null, 'error');
        }

        $result = $this->paymentGateway->retrieve($token);

        if ($result->successful) {
            $this->orderService->completePayment($order, $result->paymentId);

            Log::info('Payment callback succeeded', [
                'order_id' => $order->id,
                'payment_id' => $result->paymentId,
            ]);

            return $this->redirectToFrontend($order, 'success');
        }

        $this->orderService->failPayment($order);

        Log::warning('Payment callback failed', [
            'order_id' => $order->id,
            'error' => $result->errorMessage,
        ]);

        return $this->redirectToFrontend($order, 'failed');
    }

    public function fake(string $token): RedirectResponse
    {
        if (! config('iyzico.fake')) {
            abort(404);
        }

        return redirect()->route('payment.iyzico.callback', ['token' => $token]);
    }

    private function redirectToFrontend(?Order $order, string $status): RedirectResponse
    {
        $query = http_build_query(array_filter([
            'order_id' => $order?->id,
            'status' => $status,
        ]));

        return redirect()->away(rtrim((string) config('iyzico.frontend_result_url'), '/').'?'.$query);
    }
}
