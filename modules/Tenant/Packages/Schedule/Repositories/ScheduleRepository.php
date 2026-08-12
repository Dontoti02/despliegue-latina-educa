<?php

namespace Modules\Tenant\Packages\Schedule\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\Section;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Packages\Schedule\Helpers\ScheduleHelper;
use Modules\Tenant\Models\Schedule;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\Rol;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Shift\Enums\Shifts;

class ScheduleRepository
{
    public static function filters()
    {
        $periods = Period::select(['id', 'name'])
            ->orderBy('name', 'desc')
            ->get();

        $studyPrograms = StudyProgram::select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        $cycles = Cycle::select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        $days = SystemConfigurationHelper::getStudyDays();
        $hours = SystemConfigurationHelper::getStudyHours();

        $result = [
            'periods' => $periods,
            'study_programs' => $studyPrograms,
            'cycles' => $cycles,
            'days' => $days,
            'hours' => $hours,
        ];

        return $result;
    }

    public static function filtersByExport(Request $request)
    {
        ScheduleHelper::validateFiltersByExportRequest($request);

        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');
        $cycleId = $request->input('cycle_id');

        $roles = Rol::select()
            ->whereIn('id', [RolTenant::TEACHER, RolTenant::STUDENT])
            ->get();

        foreach ($roles as $role) {
            if ($role->id == RolTenant::TEACHER) {
                $role->persons = Teacher::selectRaw("
                    person.id, 
                    CONCAT(person.document_number, ' - ', person.names) AS names
                ")
                    ->join('person', 'teacher.person_id', 'person.id')
                    ->orderBy('person.names', 'asc')
                    ->get();
            } else {
                $role->persons = Student::selectRaw("
                    person.id, 
                    CONCAT(person.document_number, ' - ', person.names) AS names
                ")
                    ->join('person', 'student.person_id', 'person.id')
                    ->join('student_plan', function ($join) {
                        $join
                            ->on('student.id', 'student_plan.student_id')
                            ->whereNull('student_plan.deleted_at');
                    })
                    ->join('study_plan', function ($join) use ($studyProgramId) {
                        $join
                            ->on('student_plan.study_plan_id', 'study_plan.id')
                            ->whereNull('study_plan.deleted_at')
                            ->where('study_plan.study_program_id', $studyProgramId);
                    })
                    ->join('enrollment', function ($join) use ($periodId, $cycleId) {
                        $join
                            ->on('student_plan.id', 'enrollment.student_plan_id')
                            ->whereNull('enrollment.deleted_at')
                            ->where('enrollment.period_id', $periodId)
                            ->where('enrollment.cycle_id', $cycleId);
                    })
                    ->orderBy('person.names', 'asc')
                    ->get();
            }
        }

        return $roles;
    }

    public static function list(Request $request)
    {
        $user = User::authenticated();

        $isSecretary = in_array($user->rol_id, [RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);
        $isTeacher = $user->rol_id == RolTenant::TEACHER;
        $isStudent = $user->rol_id == RolTenant::STUDENT;

        ScheduleHelper::validateListRequest($request, $isSecretary);

        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');
        $cycleId = $request->input('cycle_id');

        $classrooms = Classroom::select([
            'classroom.id',
            'course.id as course_id',
            'course.name as course_name',
            'cycle.id as cycle_id',
            'cycle.name as cycle_name',
            'section.id as section_id',
            'section.name as section_name',
            'person.id as teacher_id',
            'person.names as teacher_name',
        ])
            ->join('schedule', function ($join) {
                $join
                    ->on('classroom.id', 'schedule.classroom_id')
                    ->whereNull('schedule.deleted_at');
            })
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('study_plan', function ($join) use ($isSecretary, $studyProgramId) {
                $join
                    ->on('study_plan_detail.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at')
                    ->when($isSecretary, function ($query) use ($studyProgramId) {
                        $query->where('study_plan.study_program_id', $studyProgramId);
                    });
            })
            ->join('cycle', function ($join) use ($isSecretary, $cycleId) {
                $join
                    ->on('study_plan_detail.cycle_id', 'cycle.id')
                    ->whereNull('cycle.deleted_at')
                    ->when($isSecretary, function ($query) use ($cycleId) {
                        $query->where('cycle.id', $cycleId);
                    });
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->join('section', function ($join) {
                $join
                    ->on('classroom.section_id', 'section.id')
                    ->whereNull('section.deleted_at');
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
            ->where('classroom.period_id', $periodId)
            ->when($isTeacher, function ($query) use ($user) {
                $query->where('teacher.person_id', $user->person_id);
            })
            ->when($isStudent, function ($query) use ($user) {
                $query
                    ->join('participant', function ($join) use ($user) {
                        $join
                            ->on('classroom.id', 'participant.classroom_id')
                            ->whereNull('participant.deleted_at')
                            ->where('participant.person_id', $user->person_id);
                    });
            })
            ->distinct()
            ->get();

        $result = [];
        foreach ($classrooms as $classroom) {
            $teacher = null;

            if ($classroom->teacher_id) {
                $teacher = [
                    'id' => $classroom->teacher_id,
                    'name' => $classroom->teacher_name,
                ];
            }

            $days = Schedule::select([
                'id',
                'day',
                'hour_start',
                'hour_end',
            ])
                ->where('classroom_id', $classroom->id)
                ->get();

            $participants = [];

            if ($isTeacher) {
                $participants = Participant::select([
                    'participant.id',
                    'participant.person_id',
                    'person.names',
                    'user.email',
                ])
                    ->join('person', function ($join) {
                        $join
                            ->on('participant.person_id', 'person.id')
                            ->whereNull('person.deleted_at');
                    })
                    ->join('user', function ($join) {
                        $join
                            ->on('person.id', 'user.person_id')
                            ->whereNull('user.deleted_at');
                    })
                    ->where('participant.classroom_id', $classroom->id)
                    ->orderBy('person.names', 'asc')
                    ->get();
            }

            $result[] = [
                'id' => $classroom->id,
                'course' => [
                    'id' => $classroom->course_id,
                    'name' => $classroom->course_name,
                ],
                'cycle' => [
                    'id' => $classroom->cycle_id,
                    'name' => $classroom->cycle_name,
                ],
                'section' => [
                    'id' => $classroom->section_id,
                    'name' => $classroom->section_name,
                ],
                'teacher' => $teacher,
                'days' => $days,
                'participants' => $participants,
            ];
        }

        return $result;
    }

    public static function listClassrooms(Request $request)
    {
        ScheduleHelper::validateListClassroomsRequest($request);

        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');
        $cycleId = $request->input('cycle_id');

        $hours = SystemConfigurationHelper::getStudyHours();

        $shiftOne = Shifts::ONE;

        $courses = Course::select([
            'course.id',
            'course.name',
        ])
            ->join('study_plan_detail', function ($join) use ($cycleId) {
                $join
                    ->on('course.id', 'study_plan_detail.course_id')
                    ->whereNull('study_plan_detail.deleted_at')
                    ->where('study_plan_detail.cycle_id', $cycleId);
            })
            ->join('study_plan', function ($join) use ($studyProgramId) {
                $join
                    ->on('study_plan_detail.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at')
                    ->where('study_plan.study_program_id', $studyProgramId);
            })
            ->join('classroom', function ($join) use ($periodId) {
                $join
                    ->on('study_plan_detail.id', 'classroom.study_plan_detail_id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.period_id', $periodId);
            })
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->get();

        foreach ($courses as $course) {
            $sections = Section::selectRaw("
                section.id,
                section.name,
                classroom.id as classroom_id,
                shift.name as shift,
                IF(shift.name = '$shiftOne', '$hours->start', '12:00') as hour_start,
                IF(shift.name = '$shiftOne', '12:00', '$hours->end') as hour_end,
                teacher.person_id as teacher_id
            ")
                ->join('classroom', function ($join) use ($periodId) {
                    $join
                        ->on('section.id', 'classroom.section_id')
                        ->whereNull('classroom.deleted_at')
                        ->where('classroom.period_id', $periodId);
                })
                ->join('study_plan_detail', function ($join) use ($course) {
                    $join
                        ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                        ->whereNull('study_plan_detail.deleted_at')
                        ->where('study_plan_detail.course_id', $course->id);
                })
                ->join('shift', function ($join) {
                    $join
                        ->on('classroom.shift_id', 'shift.id')
                        ->whereNull('shift.deleted_at');
                })
                ->leftJoin('teacher', function ($join) {
                    $join
                        ->on('classroom.teacher_id', 'teacher.id')
                        ->whereNull('teacher.deleted_at');
                })
                ->distinct()
                ->get();

            $course->sections = $sections;
        }

        return $courses;
    }

    public static function listClassroomsExisting(Request $request)
    {
        ScheduleHelper::validateListClassroomsRequest($request);

        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');
        $cycleId = $request->input('cycle_id');

        $hours = SystemConfigurationHelper::getStudyHours();

        $shiftOne = Shifts::ONE;

        $courses = Course::select([
            'course.id',
            'course.name',
        ])
            ->join('study_plan_detail', function ($join) use ($cycleId) {
                $join
                    ->on('course.id', 'study_plan_detail.course_id')
                    ->whereNull('study_plan_detail.deleted_at')
                    ->where('study_plan_detail.cycle_id', $cycleId);
            })
            ->join('study_plan', function ($join) use ($studyProgramId) {
                $join
                    ->on('study_plan_detail.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at')
                    ->where('study_plan.study_program_id', $studyProgramId);
            })
            ->join('classroom', function ($join) use ($periodId) {
                $join
                    ->on('study_plan_detail.id', 'classroom.study_plan_detail_id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.period_id', $periodId);
            })
            ->join('schedule', function ($join) {
                $join
                    ->on('classroom.id', 'schedule.classroom_id')
                    ->whereNull('schedule.deleted_at');
            })
            ->orderBy('course.name', 'asc')
            ->distinct()
            ->get();

        foreach ($courses as $course) {
            $sections = Section::selectRaw("
                section.id,
                section.name,
                classroom.id as classroom_id,
                shift.name as shift,
                IF(shift.name = '$shiftOne', '$hours->start', '12:00') as hour_start,
                IF(shift.name = '$shiftOne', '12:00', '$hours->end') as hour_end,
                teacher.person_id as teacher_id
            ")
                ->join('classroom', function ($join) use ($periodId) {
                    $join
                        ->on('section.id', 'classroom.section_id')
                        ->whereNull('classroom.deleted_at')
                        ->where('classroom.period_id', $periodId);
                })
                ->join('study_plan_detail', function ($join) use ($course) {
                    $join
                        ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                        ->whereNull('study_plan_detail.deleted_at')
                        ->where('study_plan_detail.course_id', $course->id);
                })
                ->join('shift', function ($join) {
                    $join
                        ->on('classroom.shift_id', 'shift.id')
                        ->whereNull('shift.deleted_at');
                })
                ->leftJoin('teacher', function ($join) {
                    $join
                        ->on('classroom.teacher_id', 'teacher.id')
                        ->whereNull('teacher.deleted_at');
                })
                ->distinct()
                ->get();

            $course->sections = $sections;
        }

        return $courses;
    }

    public static function listTeachers(Request $request)
    {
        ScheduleHelper::validateListTeachersRequest($request);

        $teachers = Teacher::select([
            'person.id',
            'person.names',
        ])
            ->join('person', 'teacher.person_id', 'person.id')
            ->whereNull('person.deleted_at')
            ->orderBy('person.names', 'asc')
            ->get();

        return $teachers;
    }

    public static function create(Request $request)
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        ScheduleHelper::validateRequest($request);

        $classroomId = $request->input('classroom_id');
        $day = $request->input('day');
        $hourStart = $request->input('hour_start');
        $hourEnd = $request->input('hour_end');

        $classroom = Classroom::findOrFail($classroomId);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        // Validar que las horas y minutos sean correctos
        ScheduleHelper::validateHoursAndMinutes($hourStart, $hourEnd);

        // Validar que no exista un horario para estas horas
        ScheduleHelper::validateNotExistsSchedule($classroomId, $day, $hourStart, $hourEnd);

        // Validar que no se cruce con otro horario
        ScheduleHelper::validateNotCross($classroom, $day, $hourStart, $hourEnd);

        Schedule::create([
            'classroom_id' => $classroomId,
            'day' => $day,
            'hour_start' => $hourStart,
            'hour_end' => $hourEnd,
        ]);

        return "Horario creado correctamente";
    }

    public static function update(int $id, Request $request)
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        ScheduleHelper::validateRequest($request, true);

        $day = $request->input('day');
        $hourStart = $request->input('hour_start');
        $hourEnd = $request->input('hour_end');

        $schedule = Schedule::findOrFail($id);

        $classroom = $schedule->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        // Validar que las horas y minutos sean correctos
        ScheduleHelper::validateHoursAndMinutes($hourStart, $hourEnd);

        // Validar que no exista un horario para estas horas
        ScheduleHelper::validateNotExistsSchedule($classroom->id, $day, $hourStart, $hourEnd, $id);

        // Validar que no se cruce con otro horario
        ScheduleHelper::validateNotCross($classroom, $day, $hourStart, $hourEnd);

        $schedule->update([
            'day' => $day,
            'hour_start' => $hourStart,
            'hour_end' => $hourEnd,
        ]);

        return "Horario actualizado correctamente";
    }

    public static function delete(int $id)
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        $schedule = Schedule::findOrFail($id);

        $classroom = $schedule->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $schedule->delete();

        return "Horario eliminado correctamente";
    }

    public static function assignTeacher(Request $request)
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        ScheduleHelper::validateAssignTeacherRequest($request);

        $classroomId = $request->input('classroom_id');
        $teacherId = $request->input('teacher_id');

        $classroom = Classroom::find($classroomId);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        if ($classroom->teacher_id == $teacherId) {
            throw new Exception('Docente ya asignado a la clase');
        }

        // Validar que el docente no tenga otra clase asignada en el mismo horario
        ScheduleHelper::validateNotCrossByTeacher($classroom->period_id, $classroomId, $teacherId);

        $classroom->update(['teacher_id' => $teacherId]);

        return 'Docente asignado a la clase correctamente';
    }

    public static function listByExport(Request $request)
    {
        $user = User::authenticated();

        $isSecretary = in_array($user->rol_id, [RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);
        $isTeacher = $user->rol_id == RolTenant::TEACHER;
        $isStudent = $user->rol_id == RolTenant::STUDENT;

        $personId = $user->person_id;

        ScheduleHelper::validateListByExportRequest($request, $isSecretary);

        $periodId = $request->input('period_id');
        $rolId = $request->input('rol_id');

        $period = Period::findOrFail($periodId);

        if ($isSecretary) {
            $isTeacher = $rolId == RolTenant::TEACHER;
            $isStudent = $rolId == RolTenant::STUDENT;

            $personId = $request->input('person_id');
        }

        $person = Person::findOrFail($personId);

        $studyProgram = null;

        if ($isStudent) {
            $studyProgram = StudyProgram::select('study_program.*')
                ->join('study_plan', function ($join) {
                    $join
                        ->on('study_program.id', 'study_plan.study_program_id')
                        ->whereNull('study_plan.deleted_at');
                })
                ->join('student_plan', function ($join) {
                    $join
                        ->on('study_plan.id', 'student_plan.study_plan_id')
                        ->whereNull('student_plan.deleted_at');
                })
                ->join('student', function ($join) use ($personId) {
                    $join
                        ->on('student_plan.student_id', 'student.id')
                        ->whereNull('student.deleted_at')
                        ->where('student.person_id', $personId);
                })
                ->join('enrollment', function ($join) use ($periodId) {
                    $join
                        ->on('student_plan.id', 'enrollment.student_plan_id')
                        ->whereNull('enrollment.deleted_at')
                        ->where('enrollment.period_id', $periodId);
                })
                ->firstOrFail();
        }

        $select = Classroom::select([
            'classroom.id',
            'course.name as course',
            'section.name as section',
            'person.names as teacher',
        ])
            ->join('schedule', function ($join) {
                $join
                    ->on('classroom.id', 'schedule.classroom_id')
                    ->whereNull('schedule.deleted_at');
            })
            ->join('study_plan_detail', function ($join) use ($periodId) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->join('section', function ($join) {
                $join
                    ->on('classroom.section_id', 'section.id')
                    ->whereNull('section.deleted_at');
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
            ->where('classroom.period_id', $periodId)
            ->when($isTeacher, function ($query) use ($personId) {
                $query->where('teacher.person_id', $personId);
            })
            ->when($isStudent, function ($query) use ($personId) {
                $query
                    ->join('participant', function ($join) use ($personId) {
                        $join
                            ->on('classroom.id', 'participant.classroom_id')
                            ->whereNull('participant.deleted_at')
                            ->where('participant.person_id', $personId);
                    });
            })
            ->distinct()
            ->get();

        $selectMap = [];
        foreach ($select as $item) {
            $name = $item->course . '<br>Sección ' . $item->section;

            if ($isStudent && $item->teacher) {
                $name .= '<br>Profesor: ' . ucwords(strtolower($item->teacher));
            }

            $days = Schedule::select([
                'id',
                'day',
                'hour_start',
                'hour_end',
            ])
                ->where('classroom_id', $item->id)
                ->get();

            $selectMap[] =  [
                'id' => $item->id,
                'name' => $name,
                'days' => $days,
            ];
        }

        $result = (object) [
            'studyProgram' => $studyProgram?->name,
            'rol' => $isTeacher ? 'DOCENTE' : 'ALUMNO',
            'person' => $person->document_number . ' - ' . $person->names,
            'period' => $period->name,
            'list' => $selectMap,
        ];

        return $result;
    }
}
