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
        $rolSecretary = RolTenant::ACADEMIC_SECRETARY;

        DB::table('option')->insert([
            [
                'name' => 'Espacios',
                'name_url' => OptionNameUrlEnum::ROOM_LIST,
                'order' => 17,
                'menu_id' => MenuEnum::PROCESOS_ACADEMICOS,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('option')->insert([
            [
                'name' => 'Dashboard Espacios',
                'name_url' => OptionNameUrlEnum::ROOM_DASHBOARD,
                'order' => 18,
                'menu_id' => MenuEnum::PROCESOS_ACADEMICOS,
                'option_id' => null,
                'is_visible' => true,
                'created_at' => now(),
            ],
        ]);

        DB::table('rol_option')->insert([
            [
                'rol_id' => $rolSecretary,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::ROOM_LIST)->value('id'),
            ],
            [
                'rol_id' => $rolSecretary,
                'option_id' => DB::table('option')->where('name_url', OptionNameUrlEnum::ROOM_DASHBOARD)->value('id'),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
