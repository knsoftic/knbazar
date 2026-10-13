<?php

namespace App\Http\Requests\Admin;

use App\Services\SettingService;
use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation rules generated from config/settings.php for one settings group.
 * Authorisation per group is enforced in SettingController.
 */
class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->definitions() as $key => $definition) {
            $rules[$key] = match ($definition['type']) {
                'bool' => ['boolean'],
                'int' => $definition['rules'] ?? ['required', 'integer'],
                'email' => $definition['rules'] ?? ['nullable', 'email', 'max:150'],
                'url' => $definition['rules'] ?? ['nullable', 'url:http,https', 'max:255'],
                'image' => ($definition['accept'] ?? null) === 'png,ico' ? UploadRules::favicon() : UploadRules::image(),
                'select' => ['required', Rule::in(array_keys($this->options($definition)))],
                'timezone' => ['required', 'timezone:all'],
                default => $definition['rules'] ?? ['nullable', 'string', 'max:1000'],
            };

            if ($definition['type'] === 'image') {
                $rules['remove_'.$key] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect($this->definitions())->mapWithKeys(fn ($d, $key) => [$key => strtolower($d['label'])])->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return app(SettingService::class)->definitionsFor((string) $this->route('group'));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, string>
     */
    public function options(array $definition): array
    {
        $options = $definition['options'] ?? [];

        return $options === 'currencies'
            ? collect(config('marketplace.currencies'))->mapWithKeys(fn ($symbol, $code) => [$code => "$code ($symbol)"])->all()
            : $options;
    }

    protected function prepareForValidation(): void
    {
        $booleans = [];

        foreach ($this->definitions() as $key => $definition) {
            if ($definition['type'] === 'bool') {
                $booleans[$key] = $this->boolean($key);
            }
        }

        $this->merge($booleans);
    }
}
