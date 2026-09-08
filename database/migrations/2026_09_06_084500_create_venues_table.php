<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_public')->default(true);

            $table->timestamps(6);

            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
