<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efront_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('ecom_customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('ecom_products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'product_id']);
        });

        // Customer "forgot password" tokens (admin users use metheme's password_reset_tokens)
        Schema::create('efront_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efront_password_reset_tokens');
        Schema::dropIfExists('efront_wishlists');
    }
};
