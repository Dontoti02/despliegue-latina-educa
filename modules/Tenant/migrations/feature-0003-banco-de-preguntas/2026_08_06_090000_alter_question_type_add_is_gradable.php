<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega la bandera `is_gradable` al catálogo de tipos de pregunta y registra
     * los nuevos tipos soportados por el motor de evaluación automática.
     *
     * `is_gradable` nace en `true` para los tipos existentes (OPTION y
     * OPTION_MULTIPLE), por lo que el comportamiento actual no cambia. La bandera
     * queda disponible para los tipos sin respuesta correcta (ensayo, archivo,
     * escala de opinión) que requieren calificación manual.
     */
    public function up(): void
    {
        Schema::table('question_type', function (Blueprint $table) {
            $table->boolean('is_gradable')->default(true)->after('data_type');
        });

        $date = Carbon::now();

        DB::table('question_type')->insert([
            [
                'key' => 'TRUE_FALSE',
                'name' => 'VERDADERO O FALSO',
                'data_type' => 'true_false',
                'is_gradable' => true,
                'order_number' => 3,
                'created_at' => $date,
            ],
            [
                'key' => 'DROPDOWN',
                'name' => 'LISTA DESPLEGABLE',
                'data_type' => 'dropdown',
                'is_gradable' => true,
                'order_number' => 4,
                'created_at' => $date,
            ],
            [
                'key' => 'SHORT_TEXT',
                'name' => 'RESPUESTA CORTA',
                'data_type' => 'short_text',
                'is_gradable' => true,
                'order_number' => 5,
                'created_at' => $date,
            ],
            [
                'key' => 'ORDERING',
                'name' => 'ORDENAR SECUENCIA',
                'data_type' => 'ordering',
                'is_gradable' => true,
                'order_number' => 6,
                'created_at' => $date,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('question_type')
            ->whereIn('key', ['TRUE_FALSE', 'DROPDOWN', 'SHORT_TEXT', 'ORDERING'])
            ->delete();

        Schema::table('question_type', function (Blueprint $table) {
            $table->dropColumn('is_gradable');
        });
    }
};
