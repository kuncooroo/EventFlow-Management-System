<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('public_slug', 180)->nullable()->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('organizer_name', 150)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->string('contact_phone', 50)->nullable();

            $table->string('mode', 20)->nullable();

            $table->dateTime('start_at', 6)->nullable();
            $table->dateTime('end_at', 6)->nullable();
            $table->string('status', 20)->default('draft');

            $table->boolean('registration_enabled')->default(false);
            $table->dateTime('registration_starts_at', 6)->nullable();
            $table->dateTime('registration_ends_at', 6)->nullable();
            $table->unsignedInteger('capacity')->nullable();

            $table->boolean('require_phone')->default(false);
            $table->boolean('require_organization')->default(false);

            $table->dateTime('published_at', 6)->nullable();
            $table->dateTime('started_at', 6)->nullable();
            $table->dateTime('completed_at', 6)->nullable();
            $table->dateTime('cancelled_at', 6)->nullable();
            $table->dateTime('archived_at', 6)->nullable();

            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);

            $table->index(['organization_id', 'status', 'start_at']);
            $table->index(['organization_id', 'start_at']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
