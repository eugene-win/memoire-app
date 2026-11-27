<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thesis_decisions', function (Blueprint $table) {
            $table->string('source', 16)->default('unclassified')->change();
        });
    }

    public function down(): void
    {
        Schema::table('thesis_decisions', function (Blueprint $table) {
            $table->string('source', 16)->default('human')->change();
        });
    }
};
