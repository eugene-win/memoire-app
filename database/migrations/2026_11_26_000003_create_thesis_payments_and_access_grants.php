<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->string('provider')->default('kkiapay');
            $table->uuid('reference')->unique();
            $table->string('provider_transaction_id')->nullable()->unique();
            $table->unsignedInteger('amount');
            $table->char('currency', 3)->default('XOF');
            $table->string('status')->default('pending')->index();
            $table->json('provider_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'thesis_id', 'status']);
        });

        Schema::create('thesis_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin');
            $table->string('status')->default('active');
            $table->timestamp('granted_at');
            $table->timestamps();

            $table->unique(['user_id', 'thesis_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_access_grants');
        Schema::dropIfExists('thesis_payments');
    }
};
