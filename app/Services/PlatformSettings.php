<?php

namespace App\Services;

use App\Models\PlatformSetting;
use RuntimeException;

class PlatformSettings
{
    public function value(string $key, mixed $default = null): mixed
    {
        return PlatformSetting::query()->where('setting_key', $key)->first()?->value ?? $default;
    }

    public function integer(string $key, int $default): int
    {
        $value = $this->value($key, $default);
        if (! is_numeric($value)) {
            throw new RuntimeException('Le paramètre '.$key.' doit être numérique.');
        }

        return (int) $value;
    }
}
