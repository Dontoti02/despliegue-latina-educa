<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Taxonomía del banco de preguntas. Ambas tablas son independientes (no se
     * asocian a ninguna entidad existente) y se comportan como etiquetas planas:
     *
     *  - `question_subject` -> Asignatura
     *  - `question_topic`   -> Tema
     *
     * La Unidad Didáctica no necesita tabla propia: se etiqueta contra `course`,
     * que ya existe (ver 2026_08_06_090300_create_question_bank_table).
     */
    public function up(): void
    {
        Schema::create('question_subject', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index('is_active');
        });

        Schema::create('question_topic', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_topic');
        Schema::dropIfExists('question_subject');
    }
};
