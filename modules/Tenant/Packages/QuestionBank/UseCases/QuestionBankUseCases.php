<?php

namespace Modules\Tenant\Packages\QuestionBank\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\QuestionBank\Repositories\QuestionBankRepository;

class QuestionBankUseCases
{
    public static function params()
    {
        try {
            return Response::success(QuestionBankRepository::params());
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            return Response::success(QuestionBankRepository::list($request));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function show(int $id)
    {
        try {
            return Response::success(QuestionBankRepository::show($id));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = QuestionBankRepository::store($request);
            DB::commit();

            return Response::success($result, 'Pregunta registrada en el banco');
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }

    public static function update(Request $request, int $id)
    {
        DB::beginTransaction();
        try {
            $result = QuestionBankRepository::update($request, $id);
            DB::commit();

            return Response::success($result, 'Pregunta actualizada');
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }

    public static function destroy(int $id)
    {
        DB::beginTransaction();
        try {
            $result = QuestionBankRepository::destroy($id);
            DB::commit();

            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }

    public static function pick(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = QuestionBankRepository::pick($request);
            DB::commit();

            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }
}
