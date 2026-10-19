<?php

namespace App\Services;

use App\Models\QrisPayment;
use App\Models\Transaction;
use App\Support\ManjoSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QrisPaymentService
{
    public function __construct(private ManjoQrisClient $client) {}

    public function issue(Transaction $transaction): QrisPayment
    {
        if ($transaction->isPaid()) {
            throw ValidationException::withMessages([
                'qris' => 'This bill is already paid.',
            ]);
        }

        if ($transaction->total_bill < 1) {
            throw ValidationException::withMessages([
                'qris' => 'This bill has no amount to pay.',
            ]);
        }

        $partnerReferenceNo = $this->partnerReference($transaction);
        $externalId = $this->externalId();

        try {
            $generated = $this->client->generateQr(
                $partnerReferenceNo,
                $transaction->total_bill,
                $externalId,
                'Daidokoro #'.$transaction->transaction_number,
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'qris' => $exception->getMessage(),
            ]);
        }

        return DB::transaction(function () use ($transaction, $partnerReferenceNo, $externalId, $generated): QrisPayment {
            $locked = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                throw ValidationException::withMessages([
                    'qris' => 'This bill is already paid.',
                ]);
            }

            if ((int) $locked->total_bill !== (int) $transaction->total_bill) {
                throw ValidationException::withMessages([
                    'qris' => 'The bill changed. Create the QR code again.',
                ]);
            }

            QrisPayment::query()
                ->where('transaction_id', $locked->id)
                ->where('status', QrisPayment::STATUS_PENDING)
                ->update(['status' => QrisPayment::STATUS_SUPERSEDED]);

            return QrisPayment::query()->create([
                'transaction_id' => $locked->id,
                'partner_reference_no' => $partnerReferenceNo,
                'external_id' => $externalId,
                'reference_no' => $generated['reference_no'],
                'qr_content' => $generated['qr_content'],
                'amount' => $locked->total_bill,
                'expires_at' => $generated['expires_at'],
                'status' => QrisPayment::STATUS_PENDING,
            ]);
        });
    }

    /**
     * @return array{status: int, body: array{responseCode: string, responseMessage: string}}
     */
    public function handleNotification(string $rawBody, string $timestamp, string $signature, string $accessToken): array
    {
        if (! $this->signatureMatches($rawBody, $timestamp, $signature, $accessToken)) {
            return $this->response(401, '4015200', 'Unauthorized');
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return $this->response(400, '4005200', 'Bad Request');
        }

        $partnerReference = $payload['originalPartnerReferenceNo'] ?? null;

        if (! is_string($partnerReference) || $partnerReference === '') {
            return $this->response(400, '4005202', 'Invalid Mandatory Field originalPartnerReferenceNo');
        }

        return DB::transaction(function () use ($payload, $partnerReference): array {
            $payment = QrisPayment::query()
                ->where('partner_reference_no', $partnerReference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return $this->response(404, '4045201', 'Transaction Not Found');
            }

            $transaction = Transaction::query()
                ->whereKey($payment->transaction_id)
                ->lockForUpdate()
                ->first();

            if ($transaction === null) {
                return $this->response(404, '4045201', 'Transaction Not Found');
            }

            if (! $this->isSuccessfulStatus($payload)) {
                $payment->update(['notify_payload' => $payload]);

                return $this->response(200, '2005200', 'Successful');
            }

            $currency = strtoupper((string) ($payload['amount']['currency'] ?? 'IDR'));
            $notifiedAmount = $payload['amount']['value'] ?? null;

            if ($currency !== 'IDR' || ! $this->sameAmount($payment->amount, $notifiedAmount) || (int) $transaction->total_bill !== (int) $payment->amount) {
                $payment->update(['notify_payload' => $payload]);

                return $this->response(400, '4005201', 'Invalid Field Format amount');
            }

            if ($transaction->isPaid()) {
                $payment->update(['notify_payload' => $payload]);

                return $this->response(200, '2005200', 'Successful');
            }

            $referenceNo = $payload['originalReferenceNo'] ?? null;

            $payment->update([
                'status' => QrisPayment::STATUS_PAID,
                'paid_at' => now(),
                'notify_payload' => $payload,
                'reference_no' => is_string($referenceNo) && $referenceNo !== '' ? $referenceNo : $payment->reference_no,
            ]);

            $transaction->update([
                'status' => 'paid',
                'paid_via' => Transaction::PAID_VIA_QRIS,
            ]);

            QrisPayment::query()
                ->where('transaction_id', $transaction->id)
                ->whereKeyNot($payment->id)
                ->where('status', QrisPayment::STATUS_PENDING)
                ->update(['status' => QrisPayment::STATUS_SUPERSEDED]);

            return $this->response(200, '2005200', 'Successful');
        });
    }

    private function signatureMatches(string $rawBody, string $timestamp, string $signature, string $accessToken): bool
    {
        $provided = strtolower(trim($signature));
        $secret = (string) config('manjo.client_secret');

        if ($provided === '' || $timestamp === '' || $secret === '') {
            return false;
        }

        $notifyPath = (string) config('manjo.notify_path');
        $candidates = [$notifyPath];
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '') {
            $candidates[] = $appUrl.$notifyPath;
        }

        foreach (array_unique($candidates) as $endpoint) {
            $expected = ManjoSignature::request('POST', $endpoint, $accessToken, $rawBody, $timestamp, $secret);

            if (hash_equals($expected, $provided)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Manjo's parameter table marks 00 as success and 03 as paid.
     * The sample body uses 06 with a success description, which the same table calls cancelled.
     *
     * @param  array<string, mixed>  $payload
     */
    private function isSuccessfulStatus(array $payload): bool
    {
        $code = $payload['latestTransactionStatus'] ?? $payload['transactionStatus'] ?? null;

        return in_array((string) $code, ['00', '03'], true);
    }

    private function sameAmount(int $expected, mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return false;
        }

        return hash_equals(
            number_format($expected, 2, '.', ''),
            number_format((float) $value, 2, '.', ''),
        );
    }

    private function partnerReference(Transaction $transaction): string
    {
        return substr('DK'.$transaction->id.'-'.now()->format('ymdHis').Str::lower(Str::random(8)), 0, 64);
    }

    private function externalId(): string
    {
        return substr(now()->format('ymdHis').Str::lower(Str::random(24)), 0, 36);
    }

    /**
     * @return array{status: int, body: array{responseCode: string, responseMessage: string}}
     */
    private function response(int $status, string $code, string $message): array
    {
        return [
            'status' => $status,
            'body' => [
                'responseCode' => $code,
                'responseMessage' => $message,
            ],
        ];
    }
}
