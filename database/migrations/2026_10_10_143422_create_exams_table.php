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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subject')->index();
            $table->enum('timer_mode', ['mode_a', 'mode_b'])->default('mode_a');
            $table->unsignedInteger('default_question_timer')->default(30);
            $table->unsignedInteger('total_global_timer')->default(1200);
            $table->integer('starting_score')->default(20);
            $table->boolean('randomize_questions')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
