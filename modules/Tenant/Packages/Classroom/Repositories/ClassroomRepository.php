<?php

namespace Modules\Tenant\Packages\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Models\Enrollment;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\Section;
use Modules\Tenant\Models\Shift;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Models\StudentPlan;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyPlanDetail;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class ClassroomRepository
{
    public static function params()
    {
        $periods = Period::select(['id', 'name'])
            ->orderBy('name', 'desc')
            ->get();

        $studyPrograms = StudyProgram::select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $studyPlans = StudyPlan::select(['id', 'name', 'study_program_id'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $cycles = Cycle::select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        $shifts = Shift::select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        $sections = Section::select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'periods' => $periods,
            'study_programs' => $studyPrograms,
            'study_plans' => $studyPlans,
            'cycles' => $cycles,
            'shifts' => $shifts,
            'sections' => $sections,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        $user = User::authenticated();

        $isTeacher = $user->rol_id === RolTenant::TEACHER;
        $isStudent = $user->rol_id === RolTenant::STUDENT;

        ClassroomHelper::validateListRequest($request, $isTeacher || $isStudent);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');
        $studyPlanId = $request->input('study_plan_id');
        $cycleId = $request->input('cycle_id');
        $shiftId = $request->input('shift_id');
        $sectionId = $request->input('section_id');

        $columns = [
            'classroom.id',
            'classroom.period_id',
            'period.name as period',
            'course.name as course',
            'person.names as teacher',
            'cycle.name as cycle',
            'shift.name as shift',
            'section.name as section',
            DB::raw('(
                SELECT COUNT(*) 
                FROM participant 
                WHERE 
                    classroom_id = classroom.id
                    AND deleted_at IS NULL
            ) as students'),
            'classroom.avatar as image',
        ];

        if ($isStudent) {
            $columns[] = 'participant.is_favorite';
        }

        $query = Classroom::select($columns)
            ->join('period', function ($join) use ($periodId) {
                $join
                    ->on('classroom.period_id', 'period.id')
                    ->whereNull('period.deleted_at')
                    ->when($periodId, function ($query) use ($periodId) {
                        $query->where('period.id', $periodId);
                    });
            })
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('study_plan', function ($join) use ($studyPlanId, $studyProgramId) {
                $join
                    ->on('study_plan_detail.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at')
                    ->when($studyPlanId, function ($query) use ($studyPlanId) {
                        $query->where('study_plan.id', $studyPlanId);
                    })
                    ->when($studyProgramId, function ($query) use ($studyProgramId) {
                        $query->where('study_plan.study_program_id', $studyProgramId);
                    });
            })
            ->join('cycle', function ($join) use ($cycleId) {
                $join
                    ->on('study_plan_detail.cycle_id', 'cycle.id')
                    ->whereNull('cycle.deleted_at')
                    ->when($cycleId, function ($query) use ($cycleId) {
                        $query->where('cycle.id', $cycleId);
                    });
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->join('shift', function ($join) use ($shiftId) {
                $join
                    ->on('classroom.shift_id', 'shift.id')
                    ->whereNull('shift.deleted_at')
                    ->when($shiftId, function ($query) use ($shiftId) {
                        $query->where('shift.id', $shiftId);
                    });
            })
            ->join('section', function ($join) use ($sectionId) {
                $join
                    ->on('classroom.section_id', 'section.id')
                    ->whereNull('section.deleted_at')
                    ->when($sectionId, function ($query) use ($sectionId) {
                        $query->where('section.id', $sectionId);
                    });
            })
            ->leftJoin('teacher', function ($join) {
                $join
                    ->on('classroom.teacher_id', 'teacher.id')
                    ->whereNull('teacher.deleted_at');
            })
            ->leftJoin('person', function ($join) {
                $join
                    ->on('teacher.person_id', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->when($isTeacher, function ($query) use ($user) {
                $query->where('teacher.person_id', $user->person_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $word =  trim($search);
                    $subquery
                        ->orWhere('course.code', 'like', "%$word%")
                        ->orWhere('course.name', 'like', "%$word%")
                        ->orWhere('course.year', 'like', "%$word%");
                });
            })
            ->when($isStudent, function ($query) use ($user) {
                $query
                    ->join('participant', function ($join) use ($user) {
                        $join
                            ->on('classroom.id', 'participant.classroom_id')
                            ->whereNull('participant.deleted_at')
                            ->where('participant.person_id', $user->person_id);
                    })
                    ->orderBy('participant.is_favorite', 'desc');
            })
            ->orderBy('period.name', 'desc')
            ->orderBy('cycle.name', 'asc')
            ->orderBy('course.name', 'asc');

        if ($isTeacher || $isStudent) {
            $result = [];

            $classrooms = $query->get();

            if ($periodId) {
                $period = Period::findOrFail($periodId);

                $result[] = [
                    'id' => $period->id,
                    'name' => $period->name,
                    'classrooms' => $classrooms,
                ];
            } else {
                $periods = Period::select(['id', 'name'])
                    ->where('is_current', false)
                    ->orderBy('name', 'desc')
                    ->get();

                foreach ($periods as $period) {
                    $classroomsByPeriod = $classrooms
                        ->where('period_id', $period->id)
                        ->values();

                    $result[] = [
                        'id' => $period->id,
                        'name' => $period->name,
                        'classrooms' => $classroomsByPeriod,
                    ];
                }
            }

            return $result;
        }

        $classrooms = $query->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $classrooms->total(),
            'items' => $classrooms->items(),
        ];

        return $result;
    }

    public static function detail(int $id)
    {
        $classroom = Classroom::findOrFail($id);

        $classroomMap = [
            'id' => $classroom->id,
            'course' => $classroom->studyPlanDetail->course->name,
            'teacher' => $classroom->teacher ? $classroom->teacher->person->names : null,
            'cycle' => $classroom->studyPlanDetail->cycle->name,
            'students' => $classroom->participants()->count(),
            'image' => $classroom->avatar,
            'is_closed' => $classroom->is_closed,
        ];

        return $classroomMap;
    }

    public static function listCourses(Request $request)
    {
        ClassroomHelper::validateListCoursesRequest($request);

        $periodId = $request->input('period_id');
        $studyPlanId = $request->input('study_plan_id');
        $cycleId = $request->input('cycle_id');
        $shiftId = $request->input('shift_id');
        $sectionId = $request->input('section_id');

        $coursesAvailable = Course::select(['id', 'name'])
            ->whereHas('studyPlanDetails', function ($studyPlanDetails) use ($periodId, $studyPlanId, $cycleId, $shiftId, $sectionId) {
                $studyPlanDetails
                    ->where('study_plan_id', $studyPlanId)
                    ->where('cycle_id', $cycleId)
                    ->whereDoesntHave('classrooms', function ($classrooms) use ($periodId, $shiftId, $sectionId) {
                        $classrooms
                            ->where('period_id', $periodId)
                            ->where('shift_id', $shiftId)
                            ->where('section_id', $sectionId);
                    });
            })
            ->orderBy('name', 'asc')
            ->get();

        $coursesAssigned = Course::select(['id', 'name'])
            ->whereHas('studyPlanDetails', function ($studyPlanDetails) use ($periodId, $studyPlanId, $cycleId, $shiftId, $sectionId) {
                $studyPlanDetails
                    ->where('study_plan_id', $studyPlanId)
                    ->where('cycle_id', $cycleId)
                    ->whereHas('classrooms', function ($classrooms) use ($periodId, $shiftId, $sectionId) {
                        $classrooms
                            ->where('period_id', $periodId)
                            ->where('shift_id', $shiftId)
                            ->where('section_id', $sectionId);
                    });
            })
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'courses_available' => $coursesAvailable,
            'courses_assigned' => $coursesAssigned,
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        ClassroomHelper::validateCreateRequest($request);

        $periodId = $request->input('period_id');
        $studyPlanId = $request->input('study_plan_id');
        $cycleId = $request->input('cycle_id');
        $shiftId = $request->input('shift_id');
        $sectionId = $request->input('section_id');
        $courseIds = $request->input('course_ids');

        $studyPlanDetails = StudyPlanDetail::select()
            ->where('study_plan_id', $studyPlanId)
            ->where('cycle_id', $cycleId)
            ->whereIn('course_id', $courseIds)
            ->whereDoesntHave('classrooms', function ($classrooms) use ($periodId, $shiftId, $sectionId) {
                $classrooms
                    ->where('period_id', $periodId)
                    ->where('shift_id', $shiftId)
                    ->where('section_id', $sectionId);
            })
            ->get();

        $createdClassrooms = [];
        foreach ($studyPlanDetails as $studyPlanDetail) {
            $createdClassrooms[] = [
                'period_id' => $periodId,
                'study_plan_detail_id' => $studyPlanDetail->id,
                'shift_id' => $shiftId,
                'section_id' => $sectionId,
            ];
        }

        Classroom::insert($createdClassrooms);

        return "Clases creadas correctamente";
    }

    public static function delete(int $id)
    {
        $classroom = Classroom::findOrFail($id);

        if ($classroom->participants()->exists()) {
            throw new Exception("No se puede eliminar la clase porque tiene estudiantes inscritos");
        }

        $classroom->delete();

        return "Clase eliminada correctamente";
    }

    public static function updateImage(int $id, Request $request)
    {
        User::authenticated(RolTenant::TEACHER);

        ClassroomHelper::validateUpdateImageRequest($request);

        $file = $request->file('file');

        $classroom = Classroom::findOrFail($id);

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        if ($classroom->avatar) {
            $classroom->avatar = 'public/' . $classroom->avatar;

            if (Storage::exists($classroom->avatar)) {
                Storage::delete($classroom->avatar);
            }
        }

        $path = $file->store('public/classroom');
        $path = str_replace('public/', '', $path);

        $classroom->update([
            'avatar' => $path,
        ]);

        return $path;
    }

    public static function updateFavorite(int $id)
    {
        $user = User::authenticated(RolTenant::STUDENT);

        $classroom = Classroom::findOrFail($id);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $participant = Participant::select()
            ->where('person_id', $user->person_id)
            ->where('classroom_id', $id)
            ->first();

        if (!$participant) {
            throw new Exception("No eres un estudiante de esta clase");
        }

        $isFavorite = !$participant->is_favorite;

        $participant->update([
            'is_favorite' => $isFavorite,
        ]);

        $message = $isFavorite ? 'marcada' : 'desmarcada';

        return "Clase $message como favorita correctamente.";
    }

    public static function enroll(int $id, int $studentId)
    {
        $classroom = Classroom::findOrFail($id);
        $student = Student::findOrFail($studentId);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $participant = Participant::select()
            ->where('person_id', $student->person_id)
            ->where('classroom_id', '!=', $id)
            ->whereHas('classroom', function ($query) use ($classroom) {
                $query->where('study_plan_detail_id', $classroom->study_plan_detail_id);
            })
            ->first();

        if ($participant) {
            $a = $participant->classroom->shift->name;
            $b = $participant->classroom->section->name;

            $scoreMinToPass = $classroom->studyPlanDetail->studyPlan->score_min_to_pass ?? 10.5;

            if ($participant->score >= $scoreMinToPass) {
                throw new Exception("¡El estudiante ya aprobó la unidad didáctica en otra clase en el turno $a, sección $b!");
            }

            throw new Exception("¡El estudiante ya se encuentra inscrito en otra clase de la misma unidad didáctica en el turno $a, sección $b!");
        }

        $participant = Participant::select()
            ->where('person_id', $student->person_id)
            ->where('classroom_id', $id)
            ->first();

        if ($participant) {
            throw new Exception('¡El estudiante ya se encuentra inscrito en la clase!');
        }

        $studentPlan = StudentPlan::select()
            ->where('student_id', $studentId)
            ->where('study_plan_id', $classroom->studyPlanDetail->study_plan_id)
            ->first();

        if (!$studentPlan) {
            throw new Exception('¡La clase no pertenece al plan de estudios del estudiante!');
        }

        if (!$studentPlan->is_active) {
            throw new Exception('¡El estudiante no está activo en el plan de estudios de la clase!');
        }

        $enrollment = Enrollment::select()
            ->where('student_plan_id', $studentPlan->id)
            ->where('period_id', $classroom->period_id)
            ->first();

        if (!$enrollment) {
            throw new Exception('¡El estudiante no está matriculado en el periodo lectivo actual!');
        }

        if ($classroom->studyPlanDetail->cycle_id != $enrollment->cycle_id) {
            throw new Exception('¡La clase no pertenece al ciclo de la matricula actual del estudiante!');
        }

        Participant::create([
            'person_id' => $student->person_id,
            'classroom_id' => $id,
        ]);

        return 'El estudiante ha sido inscrito en la clase.';
    }

    public static function remove(int $id, int $studentId)
    {
        $classroom = Classroom::findOrFail($id);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $student = Student::findOrFail($studentId);

        $participant = Participant::select()
            ->where('person_id', $student->person_id)
            ->where('classroom_id', $id)
            ->first();

        if (!$participant) {
            throw new Exception('¡El estudiante no se encuentra inscrito en la clase!');
        }

        $participant->delete();

        return 'El estudiante ha sido eliminado de la clase.';
    }

    public static function toggle(int $id)
    {
        $classroom = Classroom::findOrFail($id);

        $isClosed = !$classroom->is_closed;

        if ($isClosed) {
            $scoreMinToPass = $classroom->studyPlanDetail->studyPlan->score_min_to_pass;

            Participant::select()
                ->where('classroom_id', $id)
                ->where('score', '>=', $scoreMinToPass)
                ->update([
                    'is_approved' => true,
                ]);

            Participant::select()
                ->where('classroom_id', $id)
                ->where('score', '<', $scoreMinToPass)
                ->update([
                    'is_approved' => false,
                ]);
        }

        $classroom->update([
            'is_closed' => $isClosed,
        ]);

        $message = $isClosed ? 'cerrada' : 'abierta';

        return "Clase $message correctamente.";
    }
}
