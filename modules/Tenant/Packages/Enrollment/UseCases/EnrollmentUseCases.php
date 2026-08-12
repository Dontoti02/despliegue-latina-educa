<?php

namespace Modules\Tenant\Packages\Enrollment\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Enrollment\Repositories\EnrollmentRepository;

class EnrollmentUseCases
{
  public static function filters()
  {
    try {
      $result = EnrollmentRepository::filters();
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function list(Request $request)
  {
    try {
      $result = EnrollmentRepository::list($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function get(int $id)
  {
    try {
      $result = EnrollmentRepository::get($id);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function update(Request $request)
  {
    DB::beginTransaction();
    try {
      $result = EnrollmentRepository::update($request);
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
      $result = EnrollmentRepository::delete($id);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function validate()
  {
    try {
      $result = EnrollmentRepository::validate();
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function family(string $documentNumber)
  {
    try {
      $result = EnrollmentRepository::family($documentNumber);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function detail(Request $request)
  {
    try {
      $result = EnrollmentRepository::detail($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function listClassrooms(Request $request)
  {
    try {
      $result = EnrollmentRepository::listClassrooms($request);
      return Response::success($result);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }

  public static function set(Request $request)
  {
    DB::beginTransaction();
    try {
      $result = EnrollmentRepository::set($request);
      DB::commit();
      return Response::success($result);
    } catch (Exception $e) {
      DB::rollBack();
      return Response::error($e->getMessage());
    }
  }

  public static function download(Request $request)
  {
    try {
      [$binary, $type, $filename] = EnrollmentRepository::download($request);
      return Response::file($binary, $type, $filename);
    } catch (Exception $e) {
      return Response::error($e->getMessage());
    }
  }
}
