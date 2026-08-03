<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        if ($token === '') {
            return $this->redirectToFrontend(null, 'error');
        }

        $order = Order::query()->where('iyzico_token', $token)->first();

        if ($order === null) {
            return $this->redirectToFrontend(null, 'error');
        }

        $result = $this->paymentGateway->retrieve($token);

        if ($result->successful) {
            $this->orderService->completePayment($order, $result->paymentId);

            return $this->redirectToFrontend($order, 'success');
        }

        $this->orderService->failPayment($order);

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
