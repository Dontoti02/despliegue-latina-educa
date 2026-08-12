<?php

namespace Modules\Tenant\Packages\QuestionBank\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\QuestionBank\Repositories\QuestionTaxonomyRepository;

class QuestionTaxonomyUseCases
{
    public static function list(string $type, Request $request)
    {
        try {
            return Response::success(QuestionTaxonomyRepository::list($type, $request));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function store(string $type, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = QuestionTaxonomyRepository::store($type, $request);
            DB::commit();

            return Response::success($result, 'Registro creado');
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }

    public static function update(string $type, Request $request, int $id)
    {
        DB::beginTransaction();
        try {
            $result = QuestionTaxonomyRepository::update($type, $request, $id);
            DB::commit();

            return Response::success($result, 'Registro actualizado');
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }

    public static function destroy(string $type, int $id)
    {
        DB::beginTransaction();
        try {
            $result = QuestionTaxonomyRepository::destroy($type, $id);
            DB::commit();

            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();

            return Response::error($e->getMessage());
        }
    }
}
