<?php

namespace App\Models;

use Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'driver', 'description', 'instructions', 'details', 'currency', 'requires_proof', 'is_active', 'sort_order'])]
#[Hidden(['config'])]
class PaymentGateway extends Model
{
    /** @use HasFactory<PaymentGatewayFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'config' => 'encrypted:array', // secrets for future automated gateways
            'requires_proof' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function logoUrl(): ?string
    {
        return storage_url($this->logo);
    }

    /**
     * Account detail rows that have a value, e.g. [['label' => 'IBAN', 'value' => 'PK36...']].
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function detailRows(): array
    {
        return collect($this->details ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['label'] ?? null) && filled($row['value'] ?? null))
            ->map(fn (array $row) => ['label' => (string) $row['label'], 'value' => (string) $row['value']])
            ->values()
            ->all();
    }
}
