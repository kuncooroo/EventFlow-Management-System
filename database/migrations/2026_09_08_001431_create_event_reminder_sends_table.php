<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_reminder_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('occurrence_key', 255);
            $table->dateTime('sent_at', 6);
            $table->dateTime('created_at', 6);

            $table->unique(['event_id', 'occurrence_key']);
            $table->index('occurrence_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_reminder_sends');
    }
};
