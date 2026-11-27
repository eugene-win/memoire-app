<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thesis_versions', function (Blueprint $table) {
            $table->json('metadata_snapshot')->nullable();
            $table->timestamp('rights_confirmed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('thesis_versions', function (Blueprint $table) {
            $table->dropColumn(['metadata_snapshot', 'rights_confirmed_at']);
        });
    }
};
