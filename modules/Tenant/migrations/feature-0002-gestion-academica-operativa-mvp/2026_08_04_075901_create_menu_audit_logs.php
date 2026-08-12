<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Packages\User\Enums\MenuEnum;
use Modules\Tenant\Packages\User\Enums\OptionNameUrlEnum;
use Modules\Tenant\Packages\User\Enums\RolTenant;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {

            DB::table('menu')->updateOrInsert(
                ['id' => MenuEnum::AUDIT_LOG],
                [
                    'name' => 'Auditoria y Logs',
                    'order' => 10,
                    'created_at' => now(),
                ]
            );

            DB::table('option')->updateOrInsert(
                ['name_url' => OptionNameUrlEnum::AUDIT_LOGS],
                [
                    'name' => 'Logs de Auditoría',
                    'menu_id' => MenuEnum::AUDIT_LOG,
                    'option_id' => null,
                    'order' => 1,
                    'is_visible' => true,
                    'created_at' => now(),
                ]
            );

            $optionId = DB::table('option')
                ->where('name_url', OptionNameUrlEnum::AUDIT_LOGS)
                ->value('id');

            DB::table('rol_option')->updateOrInsert(
                [
                    'rol_id' => RolTenant::ADMINISTRADOR,
                    'option_id' => $optionId,
                ],
                []
            );
        });
    }

    public function down(): void
    {
        DB::transaction(function () {

            $optionId = DB::table('option')
                ->where('name_url', OptionNameUrlEnum::AUDIT_LOGS)
                ->value('id');

            if ($optionId) {
                DB::table('rol_option')
                    ->where('option_id', $optionId)
                    ->delete();
            }

            DB::table('option')
                ->where('name_url', OptionNameUrlEnum::AUDIT_LOGS)
                ->delete();

            DB::table('menu')
                ->where('id', MenuEnum::AUDIT_LOG)
                ->delete();
        });
    }
};
