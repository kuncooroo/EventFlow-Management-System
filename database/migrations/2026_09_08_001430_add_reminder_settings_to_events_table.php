<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('reminder_enabled')->default(false)->after('require_organization');
            $table->unsignedInteger('reminder_hours_before')->nullable()->after('reminder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['reminder_enabled', 'reminder_hours_before']);
        });
    }
};
