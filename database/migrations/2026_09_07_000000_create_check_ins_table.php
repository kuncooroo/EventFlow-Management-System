<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('operator_user_id')->constrained('users')->restrictOnDelete();
            $table->string('method', 16);
            $table->dateTime('checked_in_at', 6);
            $table->dateTime('created_at', 6);

            $table->unique('registration_id');
            $table->unique('ticket_id');
            $table->index('checked_in_at');
            $table->index(['operator_user_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
