<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_field_id')->constrained()->restrictOnDelete();
            $table->string('field_label_snapshot', 200);
            $table->string('field_type_snapshot', 30);
            $table->text('answer_text')->nullable();
            $table->json('answer_json')->nullable();

            $table->timestamps(6);

            $table->unique(['registration_id', 'registration_field_id'], 'reg_answers_reg_field_unique');
            $table->index('registration_field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_answers');
    }
};
