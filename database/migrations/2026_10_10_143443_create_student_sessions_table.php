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
        Schema::create('student_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->integer('current_score')->default(20);
            $table->integer('safe_score')->default(0);
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->enum('status', ['ongoing', 'finished', 'eliminated'])->default('ongoing');
            $table->boolean('lifeline_5050_used')->default(false);
            $table->json('question_order')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'exam_id', 'status']);
            $table->index(['exam_id', 'status', 'current_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_sessions');
    }
};
