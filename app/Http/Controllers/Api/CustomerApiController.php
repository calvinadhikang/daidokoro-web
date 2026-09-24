<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerDirectoryService;
use App\Support\CustomerFormatter;
use App\Support\TransactionApiFormatter;
use Illuminate\Http\JsonResponse;

class CustomerApiController extends Controller
{
    public function __construct(private CustomerDirectoryService $customers) {}

    public function index(): JsonResponse
    {
        return response()->json(
            $this->customers
                ->list()
                ->map(fn (Customer $customer) => CustomerFormatter::format($customer))
                ->values()
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = $this->customers->create(
            $validated['name'],
            $validated['phone'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'customer' => CustomerFormatter::format($customer),
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json($this->formatDetail($customer));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();

        $customer = $this->customers->update(
            $customer,
            $validated['name'],
            $validated['phone'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'customer' => $this->formatDetail($customer),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDetail(Customer $customer): array
    {
        $transactions = $this->customers->transactionsFor($customer);

        $payload = CustomerFormatter::format($customer);
        $payload['transactions_count'] = $transactions->count();
        $payload['transactions'] = $transactions
            ->map(fn ($transaction) => TransactionApiFormatter::formatListItem($transaction))
            ->values()
            ->all();

        return $payload;
    }
}
