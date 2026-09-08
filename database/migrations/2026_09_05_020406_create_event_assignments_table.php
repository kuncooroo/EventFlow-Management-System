<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('organization_membership_id')->constrained('organization_memberships')->restrictOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps(6);

            $table->unique(['event_id', 'organization_membership_id'], 'event_membership_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_assignments');
    }
};
