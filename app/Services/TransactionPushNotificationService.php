<?php

namespace App\Services;

use App\Models\PushDevice;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionPushNotificationService
{
    public function __construct(private ExpoPushService $expoPush) {}

    public function notifyCustomerOrderCheckedOut(Transaction $transaction): void
    {
        try {
            $tokens = PushDevice::query()
                ->pluck('expo_push_token')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($tokens === []) {
                return;
            }

            $transaction->loadCount('items');

            $this->expoPush->sendToMany(
                $tokens,
                'New customer order',
                $this->formatOrderBody($transaction),
                [
                    'screen' => 'transaction_detail',
                    'id' => (string) $transaction->id,
                    'source' => 'customer_checkout',
                ],
            );
        } catch (Throwable $exception) {
            Log::warning('Customer order push notification failed.', [
                'transaction_id' => $transaction->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function formatOrderBody(Transaction $transaction): string
    {
        $serviceType = match ($transaction->service_type) {
            'takeaway' => 'Takeaway',
            default => 'Dine-in',
        };

        $itemCount = (int) ($transaction->items_count ?? $transaction->items()->count());
        $itemLabel = $itemCount === 1 ? '1 item' : "{$itemCount} items";
        $total = number_format($transaction->total_bill, 0, ',', '.');

        return "{$transaction->customer_name} · {$serviceType} · {$itemLabel} · Rp {$total}";
    }
}
