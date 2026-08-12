<?php

namespace Modules\Tenant\Packages\Person\Repositories;

use Illuminate\Http\Request;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Packages\Person\Helpers\PersonHelper;

class PersonRepository
{
    public static function search(Request $request)
    {
        PersonHelper::validateSearchRequest($request);

        $type = $request->input('type');
        $value = $request->input('value');
        $toCreate = $request->input('to_create', false);

        $columns = [
            'person.id',
            'teacher.id as teacher_id',
            'student.id as student_id',
            'person.document_number',
            'person.names',
        ];

        $joinTeacher = $type === 'teacher' ? 'join' : 'leftJoin';
        $joinStudent = $type === 'student' ? 'join' : 'leftJoin';

        if ($toCreate) {
            $columns[] = 'person.document_type';
            $columns[] = 'person.birth_date';
            $columns[] = 'person.sex';
            $columns[] = 'person_additional_data.civil_status';
            $columns[] = 'person_additional_data.country';
            $columns[] = 'person_additional_data.department';
            $columns[] = 'person_additional_data.province';
            $columns[] = 'person_additional_data.district';
        }

        $result = Person::select($columns)
            ->{$joinTeacher}('teacher', function ($join) {
                $join
                    ->on('person.id', '=', 'teacher.person_id')
                    ->whereNull('teacher.deleted_at');
            })
            ->{$joinStudent}('student', function ($join) {
                $join
                    ->on('person.id', '=', 'student.person_id')
                    ->whereNull('student.deleted_at');
            })
            ->leftJoin('person_additional_data', function ($join) {
                $join->on('person.id', '=', 'person_additional_data.person_id');
            })
            ->when($value, function ($query) use ($value) {
                $search = trim($value);
                $query->where(function ($query) use ($search) {
                    $query
                        ->orWhere('person.document_number', 'LIKE', "%{$search}%")
                        ->orWhere('person.names', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('person.names', 'asc')
            ->limit(10)
            ->get();

        return $result;
    }
}
