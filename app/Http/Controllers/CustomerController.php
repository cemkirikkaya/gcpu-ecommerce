<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $service
    ) {}

    public function index(): View
    {
        $customers = $this->service->getAll();

        return view('customers.index', compact('customers'));
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->service->create(
            $request->validated()
        );

        return back();
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->service->update(
            $customer,
            $request->validated()
        );

        return back();
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->service->delete($customer);

        return back();
    }
}
