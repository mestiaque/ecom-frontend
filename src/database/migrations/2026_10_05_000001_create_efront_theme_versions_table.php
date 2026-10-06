<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every published storefront theme (Admin → Storefront Theme → History), newest kept, oldest pruned
        Schema::create('efront_theme_versions', function (Blueprint $table) {
            $table->id();
            $table->json('settings');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efront_theme_versions');
    }
};
