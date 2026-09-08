<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users');
            $table->char('registration_code', 26);
            $table->string('status', 20);
            $table->string('attendee_name', 150);
            $table->string('attendee_email', 254);
            $table->string('attendee_phone', 50)->nullable();
            $table->string('attendee_organization', 180)->nullable();
            $table->dateTime('registered_at', 6);
            $table->dateTime('cancelled_at', 6)->nullable();

            $table->timestamps(6);

            $table->unique('registration_code');
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'attendee_email']);
            $table->index(['event_id', 'attendee_name']);
            $table->index(['event_id', 'ticket_type_id', 'status']);
            $table->index(['ticket_type_id', 'status']);
            $table->index(['event_id', 'registered_at']);
        });

        DB::statement("ALTER TABLE registrations ADD CONSTRAINT registrations_status_check CHECK (status IN ('confirmed', 'cancelled'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
