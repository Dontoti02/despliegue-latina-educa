<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Las tres dimensiones de clasificación del banco funcionan como etiquetas
     * N:N e independientes entre sí. Se usan pivotes explícitos (en vez de una
     * tabla polimórfica) porque el filtro combina las tres con AND y así cada
     * dimensión mantiene su propio índice.
     */
    public function up(): void
    {
        Schema::create('question_bank_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')
                ->references('id')
                ->on('question_bank')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('course_id')
                ->references('id')
                ->on('course')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['question_bank_id', 'course_id'], 'question_bank_course_unique');
            $table->index('course_id');
        });

        Schema::create('question_bank_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')
                ->references('id')
                ->on('question_bank')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('question_subject_id')
                ->references('id')
                ->on('question_subject')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['question_bank_id', 'question_subject_id'], 'question_bank_subject_unique');
            $table->index('question_subject_id');
        });

        Schema::create('question_bank_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')
                ->references('id')
                ->on('question_bank')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->foreignId('question_topic_id')
                ->references('id')
                ->on('question_topic')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unique(['question_bank_id', 'question_topic_id'], 'question_bank_topic_unique');
            $table->index('question_topic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank_topic');
        Schema::dropIfExists('question_bank_subject');
        Schema::dropIfExists('question_bank_course');
    }
};
