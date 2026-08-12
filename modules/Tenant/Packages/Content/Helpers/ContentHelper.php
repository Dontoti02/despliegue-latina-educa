<?php

namespace Modules\Tenant\Packages\Content\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class ContentHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $validator = Validator::make($request->all(), [
            "content_group_id"      => "required|numeric|exists:content_group,id",
            "evaluation_group_id"   => "required_if:type,task,evaluation|numeric|exists:evaluation_group,id",
            "title"                 => "required|string|max:255|unique:content,title,$exceptId,id,content_group_id,$request->content_group_id",
            "description"           => "nullable|string",
            "type"                  => "required|string|in:content,task,evaluation",
            "date_start"            => "required_if:type,task,evaluation|date",
            "date_limit"            => "required_if:type,task,evaluation|date",
            "score"                 => "required_if:type,task,evaluation|numeric|between:0,20",
            "has_evaluation_form"   => "required_if:type,evaluation|boolean",
            "form"                  => "required_if:has_evaluation_form,true|array",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDateStartAndLimit(Request $request, $dateStart = null)
    {
        if ($request->filled('date_start') && $request->filled('date_limit')) {

            $date = Carbon::now()->startOfDay();
            $date_start = Carbon::parse($request->date_start)->startOfDay();
            $date_limit = Carbon::parse($request->date_limit);

            $dateStart = $dateStart ? Carbon::parse($dateStart)->startOfDay() : null;

            if (!$dateStart || $dateStart != $date_start) {
                if ($date_start < $date) {
                    throw new Exception("La fecha de inicio debe ser mayor o igual a la fecha actual.");
                }
            }

            if ($date_limit <= Carbon::now()) {
                throw new Exception("La fecha límite debe ser mayor a la fecha actual.");
            }
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $content = Content::find($request->model_id);

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $result = (object) [
            'polymorphic' => $content,
            'classroom_id' => $classroom->id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        User::authenticated(RolTenant::TEACHER);

        $content = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }
    }


    public static function validateReportRequest($request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"  => "required|numeric|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
