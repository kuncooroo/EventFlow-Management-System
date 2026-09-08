<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->restrictOnDelete();
            $table->char('ticket_code', 26);
            $table->char('qr_token', 64);
            $table->dateTime('issued_at', 6);

            $table->timestamps(6);

            $table->unique('registration_id');
            $table->unique('ticket_code');
            $table->unique('qr_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
