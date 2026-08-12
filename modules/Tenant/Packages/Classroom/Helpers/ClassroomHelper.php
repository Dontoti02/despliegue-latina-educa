<?php

namespace Modules\Tenant\Packages\Classroom\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class ClassroomHelper
{
    public static function validateListRequest(Request $request, bool $isTeacherOrStudent = false)
    {
        $paginateRequired = $isTeacherOrStudent ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [
            'page'              => $paginateRequired . '|integer|gt:0',
            'size'              => $paginateRequired . '|integer|gt:0',
            'search'            => 'nullable|string',
            'period_id'         => 'nullable|integer|exists:period,id',
            'study_program_id'  => 'nullable|integer|exists:study_program,id',
            'study_plan_id'     => 'nullable|integer|exists:study_plan,id',
            'cycle_id'          => 'nullable|integer|exists:cycle,id',
            'shift_id'          => 'nullable|integer|exists:shift,id',
            'section_id'        => 'nullable|integer|exists:section,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListCoursesRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_id'             => 'required|integer|exists:period,id',
            'study_plan_id'         => 'required|integer|exists:study_plan,id',
            'cycle_id'              => 'required|integer|exists:cycle,id',
            'shift_id'              => 'required|integer|exists:shift,id',
            'section_id'            => 'required|integer|exists:section,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateCreateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_id'             => 'required|integer|exists:period,id',
            'study_plan_id'         => 'required|integer|exists:study_plan,id',
            'cycle_id'              => 'required|integer|exists:cycle,id',
            'shift_id'              => 'required|integer|exists:shift,id',
            'section_id'            => 'required|integer|exists:section,id',
            'course_ids'            => 'required|array|min:1',
            'course_ids.*'          => 'integer|exists:course,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateImageRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'  => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAccess(int $id, string $rolesString = 'academic_secretary,administrador,teacher,student')
    {
        $rolesExplode = explode(',', $rolesString);
        $rolesExplode = array_map('trim', $rolesExplode);
        $rolesExplode = array_map('strtoupper', $rolesExplode);

        $roles = [];
        foreach ($rolesExplode as $roleExplode) {
            $roles[] = constant("Modules\Tenant\Packages\User\Enums\RolTenant::$roleExplode");
        }

        $user = User::authenticated($roles);

        $isSecretary = $user->rol_id === RolTenant::ACADEMIC_SECRETARY;
        $isAdmin = $user->rol_id === RolTenant::ADMINISTRADOR;

        $isTeacher = Teacher::select()
            ->where('person_id', $user->person_id)
            ->whereHas('classrooms', function ($query) use ($id) {
                $query->where('id', $id);
            })
            ->exists();

        $isStudent = Student::select()
            ->where('person_id', $user->person_id)
            ->whereHas('person.participants', function ($query) use ($id) {
                $query->where('classroom_id', $id);
            })
            ->exists();

        $hasAccess = false;
        $message = "Sin acceso a esta clase";

        if (in_array(RolTenant::ACADEMIC_SECRETARY, $roles) && $isSecretary) {
            $hasAccess = true;
        }

        if (in_array(RolTenant::ADMINISTRADOR, $roles) && $isAdmin) {
            $hasAccess = true;
        }

        if (in_array(RolTenant::TEACHER, $roles) && $isTeacher) {
            $hasAccess = true;
        }

        if (in_array(RolTenant::STUDENT, $roles) && $isStudent) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            throw new Exception($message);
        }
    }
}
