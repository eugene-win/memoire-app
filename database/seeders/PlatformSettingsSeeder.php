<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'setting_key' => 'external_access_price_xof',
                'setting_group' => 'payments',
                'value' => 1000,
                'is_public' => false,
                'description' => 'Tarif d’accès intégral externe par mémoire, en FCFA (XOF).',
            ],
            [
                'setting_key' => 'maximum_pdf_size_bytes',
                'setting_group' => 'deposits',
                'value' => 5242880,
                'is_public' => false,
                'description' => 'Taille maximale d’un PDF déposée, soit 5 Mio.',
            ],
            [
                'setting_key' => 'maximum_similarity_percent',
                'setting_group' => 'analysis',
                'value' => 20,
                'is_public' => false,
                'description' => 'Seuil fixe de similitude; les résultats strictement supérieurs sont rejetés.',
            ],
            [
                'setting_key' => 'preview_page_count',
                'setting_group' => 'catalog',
                'value' => 1,
                'is_public' => false,
                'description' => 'Nombre maximal de pages servies dans l’aperçu public (1 à 50).',
            ],
        ] as $setting) {
            PlatformSetting::firstOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting,
            );
        }
    }
}
