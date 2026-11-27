<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_settings')->insertOrIgnore([
            'setting_key' => 'preview_page_count',
            'setting_group' => 'catalog',
            'value' => json_encode(1, JSON_THROW_ON_ERROR),
            'is_public' => false,
            'description' => 'Nombre maximal de pages servies dans l’aperçu public (1 à 50).',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
