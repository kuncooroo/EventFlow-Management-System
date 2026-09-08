<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 32);
            $table->string('visibility', 16)->default('public');
            $table->boolean('is_active')->default(true);
            $table->string('disk', 64);
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes');

            $table->timestamps(6);
            $table->softDeletes('deleted_at', 6);

            $table->unique(['disk', 'path'], 'media_files_disk_path_unique');
            $table->index(['organization_id', 'category', 'is_active'], 'media_files_org_cat_active_index');
            $table->index(['event_id', 'category', 'is_active'], 'media_files_event_cat_active_index');
            $table->index('uploaded_by_user_id');
        });

        DB::statement('ALTER TABLE `media_files` ADD CONSTRAINT `media_files_category_check` CHECK (`category` IN ("organization_logo", "event_banner", "event_supporting"))');
        DB::statement('ALTER TABLE `media_files` ADD CONSTRAINT `media_files_visibility_check` CHECK (`visibility` IN ("public", "private"))');
        DB::statement('ALTER TABLE `media_files` ADD CONSTRAINT `media_files_ownership_check` CHECK (`category` = "organization_logo" OR `event_id` IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
