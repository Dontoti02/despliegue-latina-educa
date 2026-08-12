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
      $rolAcademicSecretary = RolTenant::ACADEMIC_SECRETARY;

      $menuId = DB::table('menu')->where('id', MenuEnum::PROCESOS_ACADEMICOS)->value('id');

      DB::table('option')->insert([
        [
            'name' => 'Syllabus',
            'name_url' => OptionNameUrlEnum::SYLLABUS,
            'order' => 16,
            'menu_id'=> $menuId,
            'option_id' => null,
            'is_visible' => true,
            'created_at' => now(),
        ],
      ]);

      DB::table('rol_option')->insert([
        [
            'rol_id' => $rolAcademicSecretary,
            'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::SYLLABUS)->value('id'),
        ],
      ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
