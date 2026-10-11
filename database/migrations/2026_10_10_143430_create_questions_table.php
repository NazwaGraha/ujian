<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->unsignedTinyInteger('level_tier');
            $table->text('question_text');
            $table->text('opt_a');
            $table->text('opt_b');
            $table->text('opt_c');
            $table->text('opt_d');
            $table->enum('correct_opt', ['A', 'B', 'C', 'D']);
            $table->integer('score_gain')->default(5);
            $table->integer('score_penalty')->default(5);
            $table->unsignedInteger('duration_sec')->default(30);
            $table->timestamps();

            $table->index(['exam_id', 'level_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
