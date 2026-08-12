<?php

namespace Modules\Tenant\Packages\Classroom\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Classroom\Repositories\ClassroomRepository;

class ClassroomUseCases
{
    public static function params()
    {
        try {
            $result = ClassroomRepository::params();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = ClassroomRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function detail(int $id)
    {
        try {
            $result = ClassroomRepository::detail($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listCourses(Request $request)
    {
        try {
            $result = ClassroomRepository::listCourses($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::create($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::delete($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateImage(int $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::updateImage($id, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateFavorite(int $id)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::updateFavorite($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function enroll(int $id, int $studentId)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::enroll($id, $studentId);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function remove(int $id, int $studentId)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::remove($id, $studentId);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function toggle(int $id)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::toggle($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
