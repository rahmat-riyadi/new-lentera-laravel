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
        Schema::create('student_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_quiz_id');
            $table->foreign('student_quiz_id')->on('student_quizzes')->references('id')->onDelete('CASCADE');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->foreign('question_id')->on('questions')->references('id')->onDelete('SET NULL');
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->foreign('answer_id')->on('answers')->references('id')->onDelete('SET NULL');
            $table->text('text_answer')->nullable();
            $table->float('grade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_quiz_answers');
    }
};
