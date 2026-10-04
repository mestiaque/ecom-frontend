<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer address book: many shipping / billing addresses, one default of each type.
     * Division / district / upazila come from metheme's geo_locations.
     */
    public function up(): void
    {
        Schema::create('efront_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('ecom_customers')->cascadeOnDelete();
            $table->string('type', 20)->default('shipping')->comment('shipping|billing');
            $table->string('label', 50)->nullable()->comment('Home, Office, ...');
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->foreignId('division_id')->nullable()->constrained('geo_locations')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('geo_locations')->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained('geo_locations')->nullOnDelete();
            $table->string('post_office', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('address_line', 500);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'type', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efront_addresses');
    }
};
