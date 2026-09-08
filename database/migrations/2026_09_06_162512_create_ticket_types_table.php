<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price_amount', 12, 2)->default(0.00);
            $table->char('currency', 3)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->dateTime('available_from', 6)->nullable();
            $table->dateTime('available_until', 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps(6);

            $table->unique(['event_id', 'name']);
            $table->index(['event_id', 'is_active', 'available_from', 'available_until'], 'ticket_types_avail_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
