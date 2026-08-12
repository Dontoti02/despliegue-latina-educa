<?php

namespace Modules\Tenant\Packages\Enrollment\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"                  => "required|integer|gt:0",
            "size"                  => "required|integer|gt:0",
            "student_id"            => "nullable|integer|exists:student,id",
            "period_id"             => "nullable|integer|exists:period,id",
            "enrollment_type_id"    => "nullable|integer|exists:enrollment_type,id",
            "study_program_id"      => "nullable|integer|exists:study_program,id",
            "study_plan_id"         => "nullable|integer|exists:study_plan,id",
            "shift_id"              => "nullable|integer|exists:shift,id",
            "cycle_id"              => "nullable|integer|exists:cycle,id",
            "section_id"            => "nullable|integer|exists:section,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id"                                    => "required|integer|exists:enrollment,id",
            "is_approved"                           => "required|boolean",
            "registration_date"                     => "required|date",

            "observations"                          => "nullable|string",

            "scale_id"                              => "nullable|integer|exists:scale,id",
            "scale_authorization_document_type"     => "required_with:scale_id|string",
            "scale_authorization_document_number"   => "required_with:scale_id|string",
            "scale_authorization_full_names"        => "required_with:scale_id|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDetailRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "type"          => "required|string|in:incoming,regular",
            "student_id"    => "nullable|required_if:type,regular|integer|exists:student,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListClassroomsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "type"          => "required|string|in:incoming,regular",
            "student_id"    => "nullable|required_if:type,regular|integer|exists:student,id",
            "period_id"     => "required|integer|exists:period,id",
            "study_plan_id" => "required|integer|exists:study_plan,id",
            "cycle_id"      => "required|integer|exists:cycle,id",
            "shift_id"      => "required|integer|exists:shift,id",
            "section_id"    => "required|integer|exists:section,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateSetRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "student_id"                            => "nullable|required_if:type,regular|integer|exists:student,id",

            // Datos personales
            "person.document_number"               => "required_without:student_id|string|size:8",
            "person.names"                         => "required_without:student_id|string",
            "person.birth_date"                    => "required_without:student_id|date",
            "person.sex"                           => "required_without:student_id|string",
            "person.civil_status"                  => "required_without:student_id|string",
            "person.country"                       => "required_without:student_id|string",
            "person.department"                    => "required_without:student_id|string",
            "person.province"                      => "nullable|string",
            "person.district"                      => "nullable|string",

            // Datos de contacto
            "contact.current_address"               => "required_without:student_id|string",
            "contact.permanent_address"             => "nullable|string",
            "contact.cellphone"                     => "nullable|string",
            "contact.telephone"                     => "required_without:student_id|string",
            "contact.email"                         => "required_without:student_id|string|email",

            // Datos académicos
            "academic.admission_date"               => "required_without:student_id|date",
            "academic.school_name"                  => "required_without:student_id|string",
            "academic.modular_code"                 => "required_without:student_id|string",
            "academic.graduation_year"              => "required_without:student_id|integer",
            "academic.school_type"                  => "required_without:student_id|string",
            "academic.school_category"              => "required_without:student_id|string",
            "academic.CEVA_certificate"             => "nullable|string",
            "academic.condition"                    => "required_without:student_id|string",
            "academic.observations"                 => "nullable|string",
            "academic.photo"                        => "required_without:student_id|image",
            "academic.validation"                   => "nullable|file|mimes:pdf",

            // Datos familiares
            "family.*"                              => "required_without:student_id|array|min:1",
            "family.*.document_type"                => "string",
            "family.*.document_number"              => "string",
            "family.*.names"                        => "string",
            "family.*.cellphone"                    => "string",
            "family.*.telephone"                    => "nullable|string",
            "family.*.email"                        => "string|email",
            "family.*.address"                      => "string",
            "family.*.occupation"                   => "string",
            "family.*.relationship"                 => "string",

            // Datos de matrícula
            "type"                                  => "required|string|in:incoming,regular",
            "period_id"                             => "required|integer|exists:period,id",
            "registration_date"                     => "required|date",
            "study_plan_id"                         => "required|integer|exists:study_plan,id",
            "cycle_id"                              => "required|integer|exists:cycle,id",
            "shift_id"                              => "required|integer|exists:shift,id",
            "section_id"                            => "required|integer|exists:section,id",

            "observations"                          => "nullable|string",
            "is_full_payment"                       => "required|boolean",

            // Datos de escala
            "scale_id"                              => "nullable|integer|exists:scale,id",
            "scale_authorization_document_type"     => "nullable|required_with:scale_id|string",
            "scale_authorization_document_number"   => "nullable|required_with:scale_id|string",
            "scale_authorization_full_names"        => "nullable|required_with:scale_id|string",

            // Clases a inscribir
            "classroom_ids"                         => "nullable|array|min:1",
            "classroom_ids.*"                       => "integer|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDownloadRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "type"          => "required|string|in:pdf,xlsx",
            "enrollment_id" => "required|integer|exists:enrollment,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
