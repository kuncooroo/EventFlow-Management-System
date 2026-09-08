<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 200)->nullable();
            $table->string('action', 100);
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 500)->nullable();
            $table->json('properties')->nullable();
            $table->dateTime('created_at', 6);

            $table->index(['organization_id', 'created_at'], 'activity_logs_org_created_index');
            $table->index(['event_id', 'created_at'], 'activity_logs_event_created_index');
            $table->index(['subject_type', 'subject_id'], 'activity_logs_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
