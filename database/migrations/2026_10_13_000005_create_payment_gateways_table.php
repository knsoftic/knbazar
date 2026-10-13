<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            // Driver key resolved by PaymentGatewayManager ("manual" today; automated APIs later).
            $table->string('driver', 40)->default('manual');
            $table->string('logo')->nullable();
            $table->string('description', 500)->nullable();
            $table->text('instructions')->nullable();
            // Flexible label/value account fields: Account Title, IBAN, Wallet Address, ...
            $table->json('details')->nullable();
            // Driver-specific configuration (API keys for future automated gateways, etc.)
            $table->json('config')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->boolean('requires_proof')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
