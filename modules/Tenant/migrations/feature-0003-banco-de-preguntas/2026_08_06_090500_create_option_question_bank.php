<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Packages\User\Enums\MenuEnum;
use Modules\Tenant\Packages\User\Enums\OptionNameUrlEnum;
use Modules\Tenant\Packages\User\Enums\RolTenant;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Registra la opción de menú del Banco de Preguntas.
     *
     * La gestión del banco es exclusiva de Secretaría Académica. El docente NO
     * recibe esta opción: consume el banco desde el selector embebido en el
     * constructor de evaluaciones del aula.
     */
    public function up(): void
    {
        $menuId = DB::table('menu')->where('id', MenuEnum::PROCESOS_ACADEMICOS)->value('id');

        DB::table('option')->insert([
            [
                'name' => 'Banco de Preguntas',
                'name_url' => OptionNameUrlEnum::QUESTION_BANK,
                'order' => 17,
                'menu_id' => $menuId,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('rol_option')->insert([
            [
                'rol_id' => RolTenant::ACADEMIC_SECRETARY,
                'option_id' => DB::table('option')
                    ->where('name_url', OptionNameUrlEnum::QUESTION_BANK)
                    ->value('id'),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $optionId = DB::table('option')
            ->where('name_url', OptionNameUrlEnum::QUESTION_BANK)
            ->value('id');

        if (!$optionId) {
            return;
        }

        DB::table('rol_option')->where('option_id', $optionId)->delete();
        DB::table('option')->where('id', $optionId)->delete();
    }
};
