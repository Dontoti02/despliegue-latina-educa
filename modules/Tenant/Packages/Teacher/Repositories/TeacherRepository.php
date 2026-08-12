<?php

namespace Modules\Tenant\Packages\Teacher\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Packages\Teacher\Helpers\TeacherHelper;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\User;
use Modules\Tenant\Models\WorkingCondition;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class TeacherRepository
{
    public static function search(Request $request)
    {
        $id = $request->input('id');
        $personId = $request->input('person_id');
        $search = $request->input('search');

        $teachers = Teacher::select([
            'teacher.id',
            'person.id as person_id',
            'person.document_number',
            'person.names',
        ])
            ->join('person', 'teacher.person_id', '=', 'person.id')
            ->whereNull('person.deleted_at')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->whereNull('user.deleted_at')
            ->when($id, function ($query) use ($id) {
                $query->where('teacher.id', $id);
            })
            ->when($personId, function ($query) use ($personId) {
                $query->where('person.id', $personId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $search = trim($search);
                    $q
                        ->orWhere('person.document_number', 'LIKE', "%{$search}%")
                        ->orWhere('person.names', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('person.names', 'asc')
            ->limit(10)
            ->get();

        return $teachers;
    }

    public static function params()
    {
        $workingConditions = WorkingCondition::all();

        $studyPrograms = StudyProgram::select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'working_conditions' => $workingConditions,
            'study_programs' => $studyPrograms,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        TeacherHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $teachers = Teacher::select([
            'teacher.id',
            'person.document_number',
            'person.names',
            'person.phone',
            'teacher.working_condition_id',
            'working_condition.name as working_condition_name',
            DB::raw('DATE_FORMAT(teacher.registration_date, "%d/%m/%Y") as registration_date'),
            'user.email',
            'person.sex',
            DB::raw('DATE_FORMAT(person.birth_date, "%d/%m/%Y") as birth_date'),
            'person.native_language',
            'teacher.study_program_id',
            'study_program.name as study_program_name',
            'teacher.resolution_number',
        ])
            ->join('person', function ($join) {
                $join
                    ->on('teacher.person_id', '=', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->join('user', function ($join) {
                $join
                    ->on('person.id', '=', 'user.person_id')
                    ->whereNull('user.deleted_at');
            })
            ->join('working_condition', function ($join) {
                $join
                    ->on('teacher.working_condition_id', '=', 'working_condition.id')
                    ->whereNull('working_condition.deleted_at');
            })
            ->leftJoin('study_program', function ($join) {
                $join
                    ->on('teacher.study_program_id', '=', 'study_program.id')
                    ->whereNull('study_program.deleted_at');
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $search = trim($search);
                    $query
                        ->orWhere('person.document_number', 'LIKE', "%{$search}%")
                        ->orWhere('person.names', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('person.names', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $teachers->total(),
            'items' => $teachers->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        TeacherHelper::validateCreateRequest($request);

        $documentNumber = $request->input('document_number');
        $names = $request->input('names');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $sex = $request->input('sex');
        $birthDate = $request->input('birth_date');
        $nativeLanguage = $request->input('native_language');
        $workingConditionId = $request->input('working_condition_id');
        $studyProgramId = $request->input('study_program_id');
        $registrationDate = $request->input('registration_date');
        $resolutionNumber = $request->input('resolution_number');

        $person = Person::select()
            ->where('document_number', $documentNumber)
            ->first();

        if ($person) {
            $person->update([
                'names' => $names,
                'phone' => $phone,
                'sex' => $sex,
                'birth_date' => $birthDate,
                'native_language' => $nativeLanguage,
            ]);
        } else {
            $person = Person::create([
                'document_number' => $documentNumber,
                'names' => $names,
                'phone' => $phone,
                'sex' => $sex,
                'birth_date' => $birthDate,
                'native_language' => $nativeLanguage,
            ]);
        }

        $existsTeacher = Teacher::select()
            ->where('person_id', $person->id)
            ->first();

        if ($existsTeacher) {
            throw new Exception('Ya existe un docente registrado con ese número de documento.');
        }

        Teacher::create([
            'person_id' => $person->id,
            'working_condition_id' => $workingConditionId,
            'study_program_id' => $studyProgramId,
            'registration_date' => $registrationDate,
            'resolution_number' => $resolutionNumber,
        ]);

        $user = User::select()
            ->where('email', $email)
            ->first();

        if ($user) {
            if ($user->person_id != $person->id) {
                throw new Exception('Ya esta registrada una cuenta de usuario con ese correo electrónico.');
            }
        } else {
            $user = User::create([
                'person_id' => $person->id,
                'email' => $email,
                'password' => Hash::make($documentNumber),
            ]);
        }

        $user->roles()->syncWithoutDetaching([RolTenant::TEACHER]);

        return 'Docente creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        TeacherHelper::validateUpdateRequest($request);

        $workingConditionId = $request->input('working_condition_id');
        $studyProgramId = $request->input('study_program_id');
        $registrationDate = $request->input('registration_date');
        $resolutionNumber = $request->input('resolution_number');

        $teacher = Teacher::findOrFail($id);

        $teacher->update([
            'working_condition_id' => $workingConditionId,
            'study_program_id' => $studyProgramId,
            'registration_date' => $registrationDate,
            'resolution_number' => $resolutionNumber,
        ]);

        return 'Docente actualizado correctamente.';
    }

    public static function delete(int $id)
    {
        $teacher = Teacher::findOrFail($id);

        if ($teacher->classrooms()->exists()) {
            throw new Exception('No se puede eliminar el docente porque tiene clases asociadas.');
        }

        $teacher->delete();

        $user = User::select()
            ->where('person_id', $teacher->person_id)
            ->firstOrFail();

        $user->roles()->detach(RolTenant::TEACHER);

        if (!$user->roles()->exists()) {
            $user->delete();
        }

        return "Docente eliminado correctamente.";
    }
}
