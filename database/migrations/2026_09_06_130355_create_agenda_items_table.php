<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->dateTime('start_at', 6);
            $table->dateTime('end_at', 6)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('speaker_text', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps(6);

            $table->index(['event_id', 'start_at', 'sort_order']);
            $table->index(['event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
