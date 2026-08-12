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
     */
    public function up(): void
    {
        $rolStudent = RolTenant::STUDENT;
        $rolTeacher = RolTenant::TEACHER;
        $rolAdmin = RolTenant::ADMINISTRADOR;
        $rolSecretary = RolTenant::ACADEMIC_SECRETARY;
        DB::table('menu')->updateOrInsert(
            ['id' => MenuEnum::INDICENT],
            [
                'name' => 'Incidencias',
                'order' => 11,
                'created_at' => now(),
            ]
        );

        DB::table('option')->insert([
            [
                'name' => 'Dashboard',
                'name_url' => OptionNameUrlEnum::INCIDENT_DASHBOARD,
                'order' => 1,
                'menu_id' => MenuEnum::INDICENT,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('option')->insert([
            [
                'name' => 'Registrar Incidencia',
                'name_url' => OptionNameUrlEnum::INCIDENT,
                'order' => 2,
                'menu_id' => MenuEnum::INDICENT,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('option')->insert([
            [
                'name' => 'Seguimiento de Incidencia',
                'name_url' => OptionNameUrlEnum::TRACK_INCIDENT,
                'order' => 3,
                'menu_id' => MenuEnum::INDICENT,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('option')->insert([
            [
                'name' => 'Lista de Incidencias',
                'name_url' => OptionNameUrlEnum::LIST_INCIDENTS,
                'order' => 4,
                'menu_id' => MenuEnum::INDICENT,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('option')->insert([
            [
                'name' => 'Detalle de Incidencia',
                'name_url' => OptionNameUrlEnum::INCIDENT_DETAIL,
                'order' => 5,
                'menu_id' => MenuEnum::INDICENT,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('option')->insert([
            [
                'name' => 'Tipos de Incidencias',
                'name_url' => OptionNameUrlEnum::INCIDENT_TYPES,
                'order' => 6,
                'menu_id' => MenuEnum::INDICENT,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);


        DB::table('rol_option')->insert([
            [
                'rol_id' => $rolStudent,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT)->value('id'),
            ],
            [
                'rol_id' => $rolTeacher,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT)->value('id'),
            ],
            [
                'rol_id' => $rolStudent,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::TRACK_INCIDENT)->value('id'),
            ],
            [
                'rol_id' => $rolTeacher,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::TRACK_INCIDENT)->value('id'),
            ],
            [
                'rol_id' => $rolAdmin,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::LIST_INCIDENTS)->value('id'),
            ],
            [
                'rol_id' => $rolSecretary,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::LIST_INCIDENTS)->value('id'),
            ],
            [
                'rol_id' => $rolAdmin,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT_DETAIL)->value('id'),
            ],
            [
                'rol_id' => $rolSecretary,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT_DETAIL)->value('id'),
            ],
            [
                'rol_id' => $rolAdmin,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT_TYPES)->value('id'),
            ],
            [
                'rol_id' => $rolSecretary,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT_TYPES)->value('id'),
            ],
            [
                'rol_id' => $rolAdmin,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT_DASHBOARD)->value('id'),
            ],
            [
                'rol_id' => $rolSecretary,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::INCIDENT_DASHBOARD)->value('id'),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
