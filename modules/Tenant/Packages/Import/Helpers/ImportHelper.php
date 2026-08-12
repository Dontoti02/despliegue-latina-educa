<?php

namespace Modules\Tenant\Packages\Import\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Enrollment;
use Modules\Tenant\Models\ImportDetail;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Period;

class ImportHelper
{
    public static function validateProcessRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key'   => 'required|string|in:staff,study_programs,registrations,evaluations',
            'file'  => 'required|file|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateExecuteRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key'   => 'required|string|in:staff,study_programs,registrations,evaluations',
            'title' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function extractData(UploadedFile $file)
    {
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        return $data;
    }

    public static function progress(ImportDetail &$importDetail, array &$log, int $progress)
    {
        $start = Carbon::now();
        $end = Carbon::parse($importDetail->date);
        $log[] = "$start | $progress% procesado";

        $importDetail->update([
            'progress' => $progress,
            'time_elapsed' => $end->diffInMinutes($start),
            'log' => json_encode($log),
        ]);
    }

    public static function insert(array $records, string $table)
    {
        foreach (array_chunk($records, 1000) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    public static function sendMail(string $name, string $email, string $password)
    {
        $domain = AdminSystemConfigurationHelper::getDomain();
        $subDomain = AdminSystemConfigurationHelper::getSubDomain();

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionUrl = "https://$subDomain.$domain";

        $body = EmailBodyTemplate::credentials;
        $body = str_replace('{{institutionName}}', $institutionName, $body);
        $body = str_replace('{{institutionUrl}}', $institutionUrl, $body);
        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{email}}', $email, $body);
        $body = str_replace('{{password}}', $password, $body);

        $data = (object) [
            'subject' => 'Credenciales de acceso',
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }

    public static function generateStatusForImport()
    {
        $periodIds = Period::select()
            ->where('is_current', false)
            ->pluck('id')
            ->toArray();

        $participants = Participant::select([
            'participant.id',
            'study_plan_detail.study_plan_id',
            'study_plan.score_min_to_pass',
            'study_plan_detail.cycle_id',
            'classroom.period_id',
            'course.credits',
            'participant.classroom_id',
            'student.id as student_id',
            'participant.score',
        ])
            ->join('student', function ($join) {
                $join
                    ->on('participant.person_id', 'student.person_id')
                    ->whereNull('student.deleted_at');
            })
            ->join('classroom', function ($join) use ($periodIds) {
                $join
                    ->on('participant.classroom_id', 'classroom.id')
                    ->whereNull('classroom.deleted_at')
                    ->whereIn('classroom.period_id', $periodIds);
            })
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('study_plan', function ($join) {
                $join
                    ->on('study_plan_detail.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->get();

        $updatedClassrooms = [];
        $updatedParticipants = [];

        foreach ($participants as $participant) {
            $updatedClassrooms[$participant->classroom_id] = [
                'id' => $participant->classroom_id,
                'is_closed' => true,
            ];

            $updatedParticipants[$participant->id] = [
                'id' => $participant->id,
                'is_approved' => $participant->score >= $participant->score_min_to_pass,
            ];
        }

        $enrollments = Enrollment::select([
            'enrollment.id',
            'student_plan.study_plan_id',
            'study_plan.score_min_to_pass',
            'enrollment.cycle_id',
            'enrollment.period_id',
            'period.type_min_requirement_to_pass',
            'period.min_requirement_to_pass',
            'student_plan.student_id',
        ])
            ->join('student_plan', function ($join) {
                $join
                    ->on('enrollment.student_plan_id', 'student_plan.id')
                    ->whereNull('student_plan.deleted_at');
            })
            ->join('study_plan', function ($join) {
                $join
                    ->on('student_plan.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join('period', function ($join) use ($periodIds) {
                $join
                    ->on('enrollment.period_id', 'period.id')
                    ->whereNull('period.deleted_at')
                    ->whereIn('period.id', $periodIds);
            })
            ->whereNull('enrollment.is_approved')
            ->get();

        $updatedEnrollments = [];

        foreach ($enrollments as $enrollment) {
            $isApproved = null;

            // Cantidad de cursos
            if ($enrollment->type_min_requirement_to_pass == 0) {
                $totalApprovedClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('score', '>=', $enrollment->score_min_to_pass)
                    ->count();

                $isApproved = $totalApprovedClassrooms >= $enrollment->min_requirement_to_pass;
            }

            // Porcentaje de cursos
            if ($enrollment->type_min_requirement_to_pass == 1) {
                $totalClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->count();

                $totalApprovedClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('score', '>=', $enrollment->score_min_to_pass)
                    ->count();

                $percentageApproved = $totalClassrooms > 0 ? ($totalApprovedClassrooms / $totalClassrooms) * 100 : 0;

                $isApproved = $percentageApproved >= $enrollment->min_requirement_to_pass;
            }

            // Porcentaje de créditos
            if ($enrollment->type_min_requirement_to_pass == 2) {
                $sumCredits = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->sum('credits');

                $sumCreditsApproved = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('score', '>=', $enrollment->score_min_to_pass)
                    ->sum('credits');

                $percentageApproved = $sumCredits > 0 ? ($sumCreditsApproved / $sumCredits) * 100 : 0;

                $isApproved = $percentageApproved >= $enrollment->min_requirement_to_pass;
            }

            $updatedEnrollments[$enrollment->id] = [
                'id' => $enrollment->id,
                'is_approved' => $isApproved,
            ];
        }

        $updatedClassrooms = array_values($updatedClassrooms);
        $updatedParticipants = array_values($updatedParticipants);
        $updatedEnrollments = array_values($updatedEnrollments);

        self::updateMassive($updatedClassrooms, 'classroom', 'is_closed');
        self::updateMassive($updatedParticipants, 'participant', 'is_approved');
        self::updateMassive($updatedEnrollments, 'enrollment', 'is_approved');
    }

    public static function updateMassive(array $records, string $table, string $columnUpdate)
    {
        foreach (array_chunk($records, 1000) as $chunk) {
            $ids = [];
            $cases = [];

            foreach ($chunk as $item) {
                $id = $item['id'];
                $value = $item[$columnUpdate];

                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $ids[] = $id;
                $cases[] = "WHEN $id THEN $value";
            }

            $idsString = implode(',', $ids);
            $casesString = implode(' ', $cases);

            $sql = "
                UPDATE $table
                SET $columnUpdate = CASE id
                    $casesString
                END
                WHERE id IN ($idsString)
            ";

            DB::update($sql);
        }
    }
}
