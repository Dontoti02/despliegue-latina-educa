<?php

namespace Modules\Tenant\Packages\User\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;

class UserHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "rol_ids"           => "required|array",
            "rol_ids.*"         => "required|numeric|exists:rol,id",
            "search"            => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAccess(int $id)
    {
        $user_login = User::authenticated();

        $is_secretary = $user_login->rol_id === RolTenant::ACADEMIC_SECRETARY;
        $is_admin = $user_login->rol_id === RolTenant::ADMINISTRADOR;

        $user = User::findOrFail($id);

        if (!$is_secretary && !$is_admin && $user_login->id !== $user->id) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateCreateRequest(Request $request)
    {
        $roles = implode(',', [
            RolTenant::ADMINISTRADOR,
            RolTenant::TRAINING_ADMINISTRADOR
        ]);

        $validator = Validator::make($request->all(), [
            "document_number"   => "required|string|size:8|unique:person,document_number",
            "names"             => "required|string",
            "phone"             => "required|string|size:9",
            "email"             => "required|email|unique:user,email",
            "rol_ids"           => "required|array",
            "rol_ids.*"         => "required|numeric|exists:rol,id|in:" . $roles,
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateRequest(Request $request, User $user, $is_admin)
    {
        $exceptIdPerson = $user->person_id;
        $exceptIdUser = $user->id;
        $required = $is_admin ? 'required' : 'nullable';

        $validator = Validator::make($request->all(), [
            "document_number"   => "$required|string|size:8|unique:person,document_number,$exceptIdPerson",
            "names"             => "$required|string",
            "phone"             => "required|string|size:9",
            "email"             => "required|email|unique:user,email,$exceptIdUser",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "password" => "required|string|min:8",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePhotoRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDisableOrDeleteAccess(int $id)
    {
        $userLogin = User::authenticated();

        $user = User::findOrFail($id);

        if (!in_array($userLogin->rol_id, [RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR])) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        if ($userLogin->id == $user->id) {
            throw new Exception("No puedes desactivar o eliminar tu propio usuario");
        }

        return $user;
    }
}
