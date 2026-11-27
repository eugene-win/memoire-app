<?php

use App\Models\Thesis;
use App\Models\ThesisPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makePublishedPaymentThesis(User $owner): Thesis
{
    $thesis = Thesis::create([
        'reference' => 'EN-PAYMENT-'.str_pad((string) $owner->id, 6, '0', STR_PAD_LEFT),
        'student_id' => $owner->id,
        'title' => 'Mémoire accessible après paiement',
        'author_name' => $owner->name,
        'matricule' => $owner->matricule ?? 'ENEAM-PAYMENT-'.$owner->id,
        'department' => 'Finance et comptabilité',
        'defended_year' => now()->year,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $thesis->versions()->create([
        'version_number' => 1,
        'file_path' => 'theses/private-payment.pdf',
        'original_filename' => 'memoire.pdf',
        'file_size' => 10,
        'checksum' => hash('sha256', 'payment-thesis-'.$owner->id),
    ]);

    return $thesis;
}

function configureKkiapayForTests(array $overrides = []): void
{
    config(array_merge([
        'services.kkiapay.public_key' => 'test-public-key',
        'services.kkiapay.private_key' => 'test-private-key',
        'services.kkiapay.secret' => 'test-secret-key',
        'services.kkiapay.webhook_secret' => 'test-webhook-secret',
        'services.kkiapay.sandbox' => true,
    ], $overrides));
}

function startTestThesisPayment($test, User $reader, Thesis $thesis): ThesisPayment
{
    $test->actingAs($reader)
        ->post(route('payments.start', $thesis))
        ->assertOk()
        ->assertViewIs('payments.checkout')
        ->assertSee('Payer avec KKiaPay');

    return ThesisPayment::query()->where('user_id', $reader->id)->firstOrFail();
}

it('confirms a payment server-side and grants the external reader protected access', function () {
    Storage::fake('local');
    configureKkiapayForTests();

    $owner = User::factory()->create(['role' => 'student']);
    $reader = User::factory()->create(['role' => 'external_reader']);
    $thesis = makePublishedPaymentThesis($owner);
    Storage::disk('local')->put('theses/private-payment.pdf', "%PDF-1.4\nprivate");
    $payment = startTestThesisPayment($this, $reader, $thesis);

    Http::fake([
        'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::response([
            'status' => 'SUCCESS',
            'transactionId' => 'txn-payment-success',
            'partnerId' => $payment->reference,
            'amount' => 1000,
            'performedAt' => now()->toISOString(),
        ]),
    ]);

    $this->actingAs($reader)
        ->postJson(route('payments.confirm', $payment), ['transactionId' => 'txn-payment-success'])
        ->assertOk()
        ->assertJsonPath('status', 'paid');

    $this->assertDatabaseHas('thesis_payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'provider_transaction_id' => 'txn-payment-success',
    ]);
    $this->assertDatabaseHas('thesis_access_grants', [
        'user_id' => $reader->id,
        'thesis_id' => $thesis->id,
        'thesis_payment_id' => $payment->id,
        'status' => 'active',
    ]);

    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api-sandbox.kkiapay.me/api/v1/transactions/status'
        && $request->hasHeader('X-API-KEY', 'test-public-key')
        && $request->data() === ['transactionId' => 'txn-payment-success']);

    $this->actingAs($reader)
        ->get(route('thesis-versions.viewer', $thesis->currentVersion))
        ->assertOk()
        ->assertSee('Consultation protégée');
    $this->actingAs($reader)
        ->get(route('thesis-versions.document', $thesis->currentVersion))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    $this->actingAs($reader)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Mémoire accessible après paiement')
        ->assertSee('Paiement confirmé')
        ->assertSee('Reprendre la lecture');
    $this->assertDatabaseHas('thesis_consultations', [
        'user_id' => $reader->id,
        'thesis_id' => $thesis->id,
        'thesis_version_id' => $thesis->currentVersion->id,
        'access_type' => 'paid_external',
    ]);
    $this->actingAs($reader)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('data-can-view="true"', false);
});

it('does not grant access when the verified transaction amount or partner reference is wrong', function (array $providerResult) {
    configureKkiapayForTests();

    $owner = User::factory()->create(['role' => 'student']);
    $reader = User::factory()->create(['role' => 'external_reader']);
    $thesis = makePublishedPaymentThesis($owner);
    $payment = startTestThesisPayment($this, $reader, $thesis);
    Http::fake([
        'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::response(array_merge([
            'status' => 'SUCCESS',
            'transactionId' => 'txn-payment-invalid',
            'partnerId' => $payment->reference,
            'amount' => 1000,
        ], $providerResult)),
    ]);

    $this->actingAs($reader)
        ->postJson(route('payments.confirm', $payment), ['transactionId' => 'txn-payment-invalid'])
        ->assertUnprocessable();

    expect($payment->fresh()->status)->toBe('pending')
        ->and($reader->accessGrants()->count())->toBe(0);
})->with([
    'amount mismatch' => [['amount' => 1000.5]],
    'partner mismatch' => [['partnerId' => 'another-payment-reference']],
]);

it('keeps failed provider transactions from granting access', function () {
    configureKkiapayForTests();

    $owner = User::factory()->create(['role' => 'student']);
    $reader = User::factory()->create(['role' => 'external_reader']);
    $thesis = makePublishedPaymentThesis($owner);
    $payment = startTestThesisPayment($this, $reader, $thesis);
    Http::fake([
        'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::response([
            'status' => 'FAILED',
            'transactionId' => 'txn-payment-failed',
            'partnerId' => $payment->reference,
            'amount' => 1000,
        ]),
    ]);

    $this->actingAs($reader)
        ->postJson(route('payments.confirm', $payment), ['transactionId' => 'txn-payment-failed'])
        ->assertOk()
        ->assertJsonPath('status', 'failed');

    expect($payment->fresh()->status)->toBe('failed')
        ->and($reader->accessGrants()->count())->toBe(0)
        ->and($reader->notifications()->get()->pluck('data.event')->all())->toContain('payment_failed');
});

it('allows a pending transaction to be verified again before granting access', function () {
    configureKkiapayForTests();

    $owner = User::factory()->create(['role' => 'student']);
    $reader = User::factory()->create(['role' => 'external_reader']);
    $thesis = makePublishedPaymentThesis($owner);
    $payment = startTestThesisPayment($this, $reader, $thesis);

    Http::fake([
        'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::sequence()
            ->push([
                'status' => 'PENDING',
                'transactionId' => 'txn-payment-pending',
                'partnerId' => $payment->reference,
                'amount' => 1000,
            ])
            ->push([
                'status' => 'SUCCESS',
                'transactionId' => 'txn-payment-pending',
                'partnerId' => $payment->reference,
                'amount' => 1000,
            ]),
    ]);

    $this->actingAs($reader)
        ->postJson(route('payments.confirm', $payment), ['transactionId' => 'txn-payment-pending'])
        ->assertStatus(202)
        ->assertJsonPath('status', 'pending');
    expect($reader->accessGrants()->count())->toBe(0);

    $this->actingAs($reader)
        ->postJson(route('payments.confirm', $payment), ['transactionId' => 'txn-payment-pending'])
        ->assertOk()
        ->assertJsonPath('status', 'paid');
    expect($reader->accessGrants()->count())->toBe(1);
});

it('rejects invalid webhook secrets and processes repeated valid webhooks idempotently', function () {
    configureKkiapayForTests();

    $owner = User::factory()->create(['role' => 'student']);
    $reader = User::factory()->create(['role' => 'external_reader']);
    $thesis = makePublishedPaymentThesis($owner);
    $payment = startTestThesisPayment($this, $reader, $thesis);

    $webhookPayload = [
        'transactionId' => 'txn-webhook-success',
        'isPaymentSucces' => true,
        'amount' => 1000,
        'partnerId' => $payment->reference,
        'event' => 'transaction.success',
    ];

    $this->postJson('/api/webhooks/kkiapay', $webhookPayload, ['x-kkiapay-secret' => 'invalid-secret'])
        ->assertForbidden();

    Http::fake([
        'https://api-sandbox.kkiapay.me/api/v1/transactions/status' => Http::response([
            'status' => 'SUCCESS',
            'transactionId' => 'txn-webhook-success',
            'partnerId' => $payment->reference,
            'amount' => 1000,
        ]),
    ]);

    $this->postJson('/api/webhooks/kkiapay', $webhookPayload, ['x-kkiapay-secret' => 'test-webhook-secret'])
        ->assertOk()
        ->assertJsonPath('status', 'paid');
    $this->postJson('/api/webhooks/kkiapay', $webhookPayload, ['x-kkiapay-secret' => 'test-webhook-secret'])
        ->assertOk()
        ->assertJsonPath('status', 'paid');

    expect($reader->accessGrants()->count())->toBe(1)
        ->and($reader->notifications()->get()->pluck('data.event')->all())->toContain('payment_confirmed', 'access_granted');
    expect($reader->notifications()->count())->toBe(2);
});
