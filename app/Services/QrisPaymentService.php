<?php

namespace App\Services;

use App\Models\QrisPayment;
use App\Models\Transaction;
use App\Support\ManjoSignature;
use Illuminate\Support\Facades\Cache;
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

        return DB::transaction(function () use ($transaction, $partnerReferenceNo, $generated): QrisPayment {
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
                'external_id' => $generated['external_id'],
                'reference_no' => $generated['reference_no'],
                'qr_content' => $generated['qr_content'],
                'amount' => $locked->total_bill,
                'expires_at' => $generated['expires_at'],
                'status' => QrisPayment::STATUS_PENDING,
            ]);
        });
    }

    /**
     * @return array{status: int, body: array<string, string>}
     */
    public function issueAccessToken(string $clientKey, string $timestamp, string $signature, string $rawBody): array
    {
        $configuredClient = (string) config('manjo.client_id');

        if ($clientKey === '' || $timestamp === '' || ! $this->sameSecret($configuredClient, $clientKey)) {
            return $this->response(401, '4017300', 'Unauthorized');
        }

        $payload = json_decode($rawBody, true);
        $grantType = is_array($payload) ? ($payload['grantType'] ?? null) : null;

        if ($grantType !== 'client_credentials') {
            return $this->response(400, '4007301', 'Invalid Field Format grantType');
        }

        $publicKey = $this->providerPublicKey();

        if ($publicKey === null || ! ManjoSignature::verifyToken($clientKey, $timestamp, $signature, $publicKey)) {
            return $this->response(401, '4017300', 'Unauthorized Signature');
        }

        $token = bin2hex(random_bytes(32));
        Cache::put($this->inboundTokenKey($token), true, now()->addSeconds(900));

        return [
            'status' => 200,
            'body' => [
                'responseCode' => '2007300',
                'responseMessage' => 'Successful',
                'accessToken' => $token,
                'tokenType' => 'Bearer',
                'expiresIn' => '900',
            ],
        ];
    }

    /**
     * @return array{status: int, body: array{responseCode: string, responseMessage: string}}
     */
    public function handleNotification(string $rawBody, string $timestamp, string $signature, string $accessToken, string $requestPath = ''): array
    {
        if (! $this->signatureMatches($rawBody, $timestamp, $signature, $accessToken, $requestPath)) {
            return $this->response(401, '4015200', 'Unauthorized');
        }

        if ($accessToken !== '' && ! Cache::has($this->inboundTokenKey($accessToken))) {
            return $this->response(401, '4015201', 'Invalid Token');
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

    private function signatureMatches(string $rawBody, string $timestamp, string $signature, string $accessToken, string $requestPath): bool
    {
        $trimmed = trim($signature);
        $secret = (string) config('manjo.client_secret');

        if ($trimmed === '' || $timestamp === '' || $secret === '') {
            return false;
        }

        foreach ($this->notifyEndpoints($requestPath) as $endpoint) {
            foreach (['hex', 'base64'] as $encoding) {
                $expected = ManjoSignature::request('POST', $endpoint, $accessToken, $rawBody, $timestamp, $secret, $encoding);
                $actual = $encoding === 'hex' ? strtolower($trimmed) : $trimmed;

                if (strlen($expected) === strlen($actual) && hash_equals($expected, $actual)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function notifyEndpoints(string $requestPath): array
    {
        $paths = [
            '/v1.0/qr/qr-mpm-notify',
            '/api/v1.0/qr/qr-mpm-notify',
            '/api/manjo/qris/notify',
            '/api/manjo/qris/notify/v1.0/qr/qr-mpm-notify',
            (string) config('manjo.notify_path'),
            $requestPath,
        ];
        $appUrl = rtrim((string) config('app.url'), '/');
        $candidates = [];

        foreach (array_unique($paths) as $path) {
            if ($path === '') {
                continue;
            }

            $candidates[] = $path;

            if ($appUrl !== '') {
                $candidates[] = $appUrl.$path;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function providerPublicKey(): ?string
    {
        $path = trim((string) config('manjo.provider_public_key_path'));

        if ($path === '') {
            $path = base_path('resources/manjo/provider-public.pem');
        } elseif (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        if (! is_readable($path)) {
            return null;
        }

        $pem = file_get_contents($path);

        return is_string($pem) && str_contains($pem, 'PUBLIC KEY') ? $pem : null;
    }

    private function inboundTokenKey(string $token): string
    {
        return 'manjo.qris.inbound.'.hash('sha256', $token);
    }

    private function sameSecret(string $expected, string $actual): bool
    {
        return $expected !== '' && strlen($expected) === strlen($actual) && hash_equals($expected, $actual);
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
