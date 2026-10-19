<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\QrisPayment;
use App\Models\Transaction;
use App\Support\ManjoSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QrisPaymentTest extends TestCase
{
    use RefreshDatabase;

    private string $keyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $pem));

        $this->keyPath = tempnam(sys_get_temp_dir(), 'manjo');
        $this->assertNotFalse($this->keyPath);
        file_put_contents($this->keyPath, $pem);

        config([
            'manjo.base_url' => 'https://snap-uat.manjo.co.id/api',
            'manjo.client_id' => 'test-client',
            'manjo.client_secret' => 'test-secret',
            'manjo.merchant_id' => 'MTTEST',
            'manjo.partner_id' => 'MTTEST',
            'manjo.channel_id' => '05',
            'manjo.private_key_path' => $this->keyPath,
            'manjo.client_ip' => '127.0.0.1',
            'manjo.origin' => 'staging.daidokoro.my.id',
            'manjo.qr_validity_seconds' => 3600,
            'manjo.notify_path' => '/api/manjo/qris/notify',
        ]);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink($this->keyPath);

        parent::tearDown();
    }

    public function test_customer_can_generate_a_qris_code_for_their_bill(): void
    {
        Http::fake([
            'https://snap-uat.manjo.co.id/api/v1.0/access-token/b2b' => Http::response([
                'responseCode' => '2007300',
                'responseMessage' => 'Successful',
                'accessToken' => 'token-abc',
                'tokenType' => 'Bearer',
                'expiresIn' => '900',
            ]),
            'https://snap-uat.manjo.co.id/api/v1.0/qr/qr-mpm-generate' => Http::response([
                'responseCode' => '2004700',
                'responseMessage' => 'Successful',
                'referenceNo' => 'A0000001702',
                'qrContent' => '000201010212QRIS',
            ]),
        ]);

        $customer = $this->customer();
        $transaction = $this->bill($customer->phone, 35000);

        $response = $this
            ->withSession(['customer_id' => $customer->id])
            ->post(route('customer.order.qris', $transaction));

        $response->assertRedirect(route('customer.order.index'));
        $this->assertDatabaseHas('qris_payments', [
            'transaction_id' => $transaction->id,
            'qr_content' => '000201010212QRIS',
            'amount' => 35000,
            'status' => 'pending',
            'reference_no' => 'A0000001702',
        ]);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/v1.0/access-token/b2b')) {
                return false;
            }

            $timestamp = $request->header('X-TIMESTAMP')[0] ?? '';
            $pem = file_get_contents($this->keyPath);
            $this->assertIsString($pem);
            $expected = ManjoSignature::token('test-client', $timestamp, $pem);

            return ($request->header('X-SIGNATURE')[0] ?? '') === $expected
                && ($request->header('X-CLIENT-KEY')[0] ?? '') === 'test-client'
                && ! ctype_xdigit($expected);
        });

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/v1.0/qr/qr-mpm-generate')) {
                return false;
            }

            $timestamp = $request->header('X-TIMESTAMP')[0] ?? '';
            $expected = ManjoSignature::request(
                'POST',
                '/v1.0/qr/qr-mpm-generate',
                'token-abc',
                $request->body(),
                $timestamp,
                'test-secret',
            );
            $body = json_decode($request->body(), true);

            return ($request->header('X-SIGNATURE')[0] ?? '') === $expected
                && ($request->header('X-PARTNER-ID')[0] ?? '') === 'MTTEST'
                && ($request->header('CHANNEL-ID')[0] ?? '') === '05'
                && ($request->header('Authorization')[0] ?? '') === 'Bearer token-abc'
                && is_array($body)
                && ($body['amount']['value'] ?? null) === '35000.00'
                && ($body['additionalInfo']['dynamicAmount'] ?? null) === 'N';
        });
    }

    public function test_order_page_shows_the_active_qris_code(): void
    {
        $customer = $this->customer();
        $transaction = $this->bill($customer->phone, 35000);
        $this->payment($transaction, 'DK-REF-PAGE');
        $payment = QrisPayment::query()->where('partner_reference_no', 'DK-REF-PAGE')->firstOrFail();
        $payment->update(['qr_content' => '000201010212QRIS']);

        $page = $this
            ->withSession(['customer_id' => $customer->id])
            ->get(route('customer.order.index'));

        $page->assertOk();
        $page->assertInertia(fn ($inertia) => $inertia
            ->where('transactions.0.qris.qr_content', '000201010212QRIS')
            ->where('transactions.0.status', 'in_progress')
            ->where('transactions.0.paid_via', null)
        );
    }

    public function test_customer_cannot_generate_qris_for_someone_elses_bill(): void
    {
        $customer = $this->customer();
        $transaction = $this->bill('6281111111111', 35000);

        $this
            ->withSession(['customer_id' => $customer->id])
            ->post(route('customer.order.qris', $transaction))
            ->assertNotFound();
    }

    public function test_generating_a_new_qr_supersedes_the_previous_one(): void
    {
        Http::fake([
            'https://snap-uat.manjo.co.id/api/v1.0/access-token/b2b' => Http::response([
                'responseCode' => '2007300',
                'accessToken' => 'token-abc',
                'expiresIn' => '900',
            ]),
            'https://snap-uat.manjo.co.id/api/v1.0/qr/qr-mpm-generate' => Http::sequence()
                ->push([
                    'responseCode' => '2004700',
                    'referenceNo' => 'A1',
                    'qrContent' => 'FIRST',
                ])
                ->push([
                    'responseCode' => '2004700',
                    'referenceNo' => 'A2',
                    'qrContent' => 'SECOND',
                ]),
        ]);

        $customer = $this->customer();
        $transaction = $this->bill($customer->phone, 35000);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.order.qris', $transaction))
            ->assertRedirect();
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.order.qris', $transaction))
            ->assertRedirect();

        $this->assertDatabaseHas('qris_payments', [
            'qr_content' => 'FIRST',
            'status' => 'superseded',
        ]);
        $this->assertDatabaseHas('qris_payments', [
            'qr_content' => 'SECOND',
            'status' => 'pending',
        ]);
    }

    public function test_verified_notification_marks_the_bill_paid_via_qris(): void
    {
        $transaction = $this->bill('6281234567890', 35000);
        $payment = $this->payment($transaction, 'DK-REF-1');

        $response = $this->notify([
            'originalReferenceNo' => 'A0000001702',
            'originalPartnerReferenceNo' => 'DK-REF-1',
            'latestTransactionStatus' => '00',
            'transactionStatusDesc' => 'SUCCESS',
            'amount' => [
                'value' => '35000.00',
                'currency' => 'IDR',
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('responseCode', '2005200');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'paid',
            'paid_via' => 'qris',
        ]);
        $this->assertDatabaseHas('qris_payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_notification_with_a_bad_signature_does_not_mark_the_bill_paid(): void
    {
        $transaction = $this->bill('6281234567890', 35000);
        $this->payment($transaction, 'DK-REF-1');

        $response = $this->notify([
            'originalPartnerReferenceNo' => 'DK-REF-1',
            'latestTransactionStatus' => '00',
            'amount' => ['value' => '35000.00', 'currency' => 'IDR'],
        ], 'not-a-valid-signature');

        $response->assertUnauthorized();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'in_progress',
            'paid_via' => null,
        ]);
    }

    public function test_cancelled_notification_does_not_mark_the_bill_paid(): void
    {
        $transaction = $this->bill('6281234567890', 35000);
        $this->payment($transaction, 'DK-REF-1');

        $response = $this->notify([
            'originalPartnerReferenceNo' => 'DK-REF-1',
            'latestTransactionStatus' => '06',
            'transactionStatusDesc' => 'SUCCESS',
            'amount' => ['value' => '35000.00', 'currency' => 'IDR'],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_notification_with_a_different_amount_does_not_mark_the_bill_paid(): void
    {
        $transaction = $this->bill('6281234567890', 35000);
        $this->payment($transaction, 'DK-REF-1');

        $response = $this->notify([
            'originalPartnerReferenceNo' => 'DK-REF-1',
            'latestTransactionStatus' => '00',
            'amount' => ['value' => '10000.00', 'currency' => 'IDR'],
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_cashier_mark_paid_does_not_replace_a_qris_payment(): void
    {
        $transaction = $this->bill('6281234567890', 35000);
        $transaction->update([
            'status' => 'paid',
            'paid_via' => Transaction::PAID_VIA_QRIS,
        ]);

        $this->postJson("/api/transaction/mark-paid/{$transaction->id}")
            ->assertOk();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'paid',
            'paid_via' => 'qris',
        ]);
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
    }

    private function bill(string $phone, int $total): Transaction
    {
        return Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => $phone,
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => $total,
        ]);
    }

    private function payment(Transaction $transaction, string $reference): QrisPayment
    {
        return QrisPayment::query()->create([
            'transaction_id' => $transaction->id,
            'partner_reference_no' => $reference,
            'external_id' => 'ext-'.$reference,
            'qr_content' => '000201',
            'amount' => $transaction->total_bill,
            'expires_at' => now()->addHour(),
            'status' => QrisPayment::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function notify(array $payload, ?string $signature = null, string $token = ''): TestResponse
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $this->assertIsString($json);

        $timestamp = '2026-10-20T02:46:00+07:00';
        $signature ??= ManjoSignature::request(
            'POST',
            '/api/manjo/qris/notify',
            $token,
            $json,
            $timestamp,
            'test-secret',
        );

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_SIGNATURE' => $signature,
        ];

        if ($token !== '') {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        return $this->call('POST', '/api/manjo/qris/notify', [], [], [], $server, $json);
    }
}
