<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('setting_key', 120);
            $table->json('setting_value');
            $table->timestamps();

            $table->unique(['organization_id', 'setting_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
    }
};
