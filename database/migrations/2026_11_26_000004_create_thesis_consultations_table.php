<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_id')->constrained()->restrictOnDelete();
            $table->foreignId('thesis_version_id')->constrained('thesis_versions')->restrictOnDelete();
            $table->string('access_type');
            $table->timestamp('consulted_at');
            $table->timestamps();

            $table->index(['user_id', 'consulted_at']);
            $table->index(['thesis_id', 'consulted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_consultations');
    }
};
