<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('platform_settings')
            ->where('setting_key', 'maximum_pdf_size_bytes')
            ->first(['value']);

        if (! $setting) {
            return;
        }

        $currentValue = json_decode((string) $setting->value, true, flags: JSON_THROW_ON_ERROR);
        $maximumBytes = is_numeric($currentValue) ? min((int) $currentValue, 5242880) : 5242880;

        DB::table('platform_settings')
            ->where('setting_key', 'maximum_pdf_size_bytes')
            ->update([
                'value' => json_encode($maximumBytes, JSON_THROW_ON_ERROR),
                'description' => 'Taille maximale d’un PDF déposée, soit 5 Mio.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
    }
};
