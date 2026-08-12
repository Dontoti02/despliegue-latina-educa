<?php

namespace Modules\Tenant\Packages\Import\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Import\Helpers\ImportHelper;
use Modules\Tenant\Packages\Import\Jobs\ImportJob;
use Modules\Tenant\Models\Import;
use Modules\Tenant\Models\ImportDetail;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Packages\Import\Services\EvaluationService;
use Modules\Tenant\Packages\Import\Services\RegistrationService;
use Modules\Tenant\Packages\Import\Services\StaffService;
use Modules\Tenant\Packages\Import\Services\StudyProgramService;

class ImportRepository
{
    public static function list()
    {
        $imports = Import::select()
            ->orderBy('id', 'asc')
            ->get();

        $importsMap = [];
        foreach ($imports as $import) {
            $lastImportDetail = ImportDetail::select()
                ->where('import_id', $import->id)
                ->orderBy('date', 'desc')
                ->first();

            $importsMap[] = [
                'id' => $import->id,
                'key' => $import->key,
                'title' => $import->title,
                'last_status' => $lastImportDetail ? $lastImportDetail->status : null,
                'last_date' => $lastImportDetail ? $lastImportDetail->date : null,
            ];
        }

        return $importsMap;
    }

    public static function get(int $id)
    {
        $import = Import::findOrFail($id);

        $importDetail = ImportDetail::select()
            ->where('import_id', $import->id)
            ->where('is_current', true)
            ->first();

        if (!$importDetail) {
            throw new Exception('Aun no se ha ejecutado la importación.');
        }

        if ($importDetail->is_active) {
            throw new Exception('Importación en curso.');
        }

        $result = [
            'id' => $import->id,
            'key' => $import->key,
            'title' => $import->title,
            'status' => $importDetail->status,
            'progress' => $importDetail->progress,
            'date' => $importDetail->date,
            'time_elapsed' => $importDetail->time_elapsed,
            'log' => json_decode($importDetail->log),
            'summary' => json_decode($importDetail->summary),
        ];

        return $result;
    }

    public static function currently()
    {
        $import = Import::select([
            'import.id',
            'import.key',
            'import.title',
            'import_detail.status',
            'import_detail.progress',
            'import_detail.date',
            'import_detail.time_elapsed',
            'import_detail.log',
            'import_detail.summary',
        ])
            ->join('import_detail', 'import.id', 'import_detail.import_id')
            ->where('import_detail.is_current', true)
            ->where('import_detail.is_active', true)
            ->first();

        if ($import) {
            $import->log = json_decode($import->log);
            $import->summary = json_decode($import->summary);
        }

        return $import;
    }

    public static function history(int $id)
    {
        $import = Import::findOrFail($id);

        $importDetails = ImportDetail::select()
            ->where('import_id', $import->id)
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($importDetail) {
                return [
                    'id' => $importDetail->id,
                    'status' => $importDetail->status,
                    'date' => $importDetail->date,
                    'log' => json_decode($importDetail->log),
                ];
            });

        $result = [
            'id' => $import->id,
            'key' => $import->key,
            'title' => $import->title,
            'details' => $importDetails,
        ];

        return $result;
    }

    public static function process(Request $request)
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        ImportHelper::validateProcessRequest($request);

        $key = $request->input('key');
        $file = $request->file('file');

        $import = Import::select()
            ->where('key', $key)
            ->firstOrFail();

        $exists = ImportDetail::select()
            ->where('import_id', $import->id)
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw new Exception('Ya existe una importación en curso.');
        }

        $data = ImportHelper::extractData($file);

        if ($key == 'staff') {
            return StaffService::show($data);
        }

        if ($key == 'study_programs') {
            return StudyProgramService::show($data);
        }

        if ($key == 'registrations') {
            return RegistrationService::show($data);
        }

        if ($key == 'evaluations') {
            return EvaluationService::show($data);
        }
    }

    public static function execute(Request $request)
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        ImportHelper::validateExecuteRequest($request);

        $key = $request->input('key');
        $title = $request->input('title');

        $import = Import::select()
            ->where('key', $key)
            ->firstOrFail();

        $exists = ImportDetail::select()
            ->where('import_id', $import->id)
            ->where('is_active', true)
            ->exists();

        if ($exists) {
            throw new Exception('Ya existe una importación en curso.');
        }

        $import->update([
            'title' => $title,
        ]);

        // Usar para ejecutar en primer plano
        // set_time_limit(300);
        // self::executeJob($import, $key);

        // Usar para ejecutar en segundo plano
        ImportJob::dispatch($import, $key);

        return 'Importación iniciada';
    }

    public static function executeJob(Import $import, string $key)
    {
        $now = Carbon::now();

        $import->details()
            ->where('is_current', true)
            ->update([
                'is_current' => false,
            ]);

        $importDetail = $import->details()->create([
            'is_current' => true,
            'is_active' => true,
            'status' => 'pending',
            'progress' => 0,
            'date' => $now,
            'time_elapsed' => 0,
            'log' => json_encode(["$now | Importación iniciada"]),
        ]);

        try {
            switch ($key) {
                case 'staff':
                    $summary = StaffService::import($importDetail, $now);
                    break;
                case 'study_programs':
                    $summary = StudyProgramService::import($importDetail, $now);
                    break;
                case 'registrations':
                    $summary = RegistrationService::import($importDetail, $now);
                    break;
                case 'evaluations':
                    $summary = EvaluationService::import($importDetail, $now);
                    break;
                default:
                    throw new Exception('Tipo no admitido');
            }

            ImportHelper::generateStatusForImport();

            $date = Carbon::parse($importDetail->date);
            $now = Carbon::now();

            $log = json_decode($importDetail->log);
            $log[] = "$now | Importación completada";

            $importDetail->update([
                'is_current' => true,
                'is_active' => false,
                'status' => 'completed',
                'progress' => 100,
                'time_elapsed' => $date->diffInMinutes($now),
                'log' => json_encode($log),
                'summary' => json_encode($summary),
            ]);
        } catch (Exception $e) {
            $date = Carbon::parse($importDetail->date);
            $now = Carbon::now();

            $log = json_decode($importDetail->log);
            $aux = $e->getMessage();
            $log[] = "$now | $aux";

            $importDetail->update([
                'is_current' => true,
                'is_active' => false,
                'status' => 'failed',
                'progress' => 100,
                'time_elapsed' => $date->diffInMinutes($now),
                'log' => json_encode($log),
            ]);

            logger($e->getMessage());
        }
    }

    public static function finish(Request $request)
    {
        $key = $request->input('key');

        $import = Import::select()
            ->where('key', $key)
            ->firstOrFail();

        $import_detail = ImportDetail::select()
            ->where('import_id', $import->id)
            ->where('is_active', true)
            ->first();

        if (!$import_detail) {
            throw new Exception('No se encontró el detalle de importación actual.');
        }

        $import_detail->update([
            'is_current' => false,
            'is_active' => false
        ]);

        return "Importación finalizada";
    }

    public static function finishAll()
    {
        $import_details = ImportDetail::select()
            ->where('is_active', true)
            ->get();

        foreach ($import_details as $detail) {
            $detail->update([
                'is_current' => false,
                'is_active' => false
            ]);
        }

        return "Importaciones finalizadas";
    }
}
