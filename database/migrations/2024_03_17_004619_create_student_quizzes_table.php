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
        Schema::create('student_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('quiz_id');
            $table->foreign('quiz_id')->on('quizzes')->references('id')->onDelete('CASCADE');
            $table->string('status')->nullable();
            $table->string('layout')->nullable();
            $table->unsignedBigInteger('current_page')->nullable();
            $table->float('total_grade')->nullable();
            $table->unsignedBigInteger('attempt')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_quizzes');
    }
};
