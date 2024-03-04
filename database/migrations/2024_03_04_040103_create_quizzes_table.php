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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->dateTime('due_date')->nullable();
            $table->dateTime('start_date')->nullable();
            $table->float('pass_grade');
            $table->integer('answer_attempt')->default(0);
            $table->boolean('shuffle_questions')->default(false);
            $table->integer('question_show_number')->default(5);
            $table->boolean('show_grade')->default(false);
            $table->boolean('show_answers')->default(false);
            $table->date('activity_remember')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
