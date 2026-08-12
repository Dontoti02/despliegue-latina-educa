<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reactivo del banco de preguntas.
     *
     * `options` guarda exactamente la misma estructura JSON que `question.options`,
     * de modo que insertar un reactivo en una evaluación es una copia directa
     * (snapshot) sin transformación de datos. Por eso ningún tipo nuevo de pregunta
     * requiere migrar `form`, `question` ni `form_response`.
     */
    public function up(): void
    {
        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('question_type_key');
            $table->text('label');
            $table->json('options');
            $table->decimal('score_max', 4, 2)->default(1);
            $table->string('difficulty', 20)->default('MEDIUM');
            $table->foreignId('created_by_person_id')
                ->nullable()
                ->references('id')
                ->on('person')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index('question_type_key');
            $table->index('difficulty');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};
