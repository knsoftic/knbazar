<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runtime settings backed by the `settings` table, cached as one array.
 * Definitions and defaults live in config/settings.php.
 */
class SettingService
{
    public const CACHE_KEY = 'settings.values';

    /** @var array<string, string|null>|null */
    protected ?array $values = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return config('settings.definitions', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitionsFor(string $group): array
    {
        return array_filter($this->definitions(), fn (array $definition) => ($definition['group'] ?? null) === $group);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->values();
        $definition = $this->definitions()[$key] ?? [];

        if (array_key_exists($key, $values) && $values[$key] !== null) {
            return $this->cast($values[$key], $definition['type'] ?? 'string');
        }

        return $default ?? ($definition['default'] ?? null);
    }

    public function set(string $key, mixed $value): void
    {
        $this->persist($key, $value);
        $this->flush();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->persist($key, $value);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    /**
     * @return array<string, string|null>
     */
    protected function values(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            return $this->values = Cache::rememberForever(
                self::CACHE_KEY,
                fn () => Setting::query()->pluck('value', 'key')->all()
            );
        } catch (Throwable $e) {
            // Database not migrated yet (fresh install) - fall back to defaults.
            Log::debug('Settings unavailable, using defaults: '.$e->getMessage());

            return $this->values = [];
        }
    }

    protected function persist(string $key, mixed $value): void
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }

        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
    }

    protected function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            default => $value,
        };
    }
}
