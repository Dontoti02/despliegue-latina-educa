<?php

namespace Modules\Tenant\Packages\SyllabusManager\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\SyllabusManager\Repositories\SyllabusTemplateRepository;
use Modules\Tenant\Packages\SyllabusManager\Requests\StoreSyllabusTemplateRequest;
use Modules\Tenant\Packages\SyllabusManager\Requests\UpdateSyllabusTemplateRequest;

class SyllabusTemplateUseCases
{
    public function __construct(protected SyllabusTemplateRepository $repository)
    {
    }

    public function index(Request $request)
    {
        try {
            $search = (string) $request->input('search', '');
            $didacticUnitId = $request->filled('didactic_unit_id') ? (int) $request->input('didactic_unit_id') : null;
            $perPage = (int) $request->input('per_page', 15);

            return Response::success($this->repository->list($search, $didacticUnitId, $perPage));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function store(StoreSyllabusTemplateRequest $request)
    {
      DB::beginTransaction();
      try {
          $template = $this->repository->create($request->validated());
          DB::commit();
          return Response::success($template);
      } catch (Exception $e) {
          DB::rollBack();
          return Response::error($e->getMessage());
      }
    }

    public function edit(string $id)
    {
        try {
            return Response::success($this->repository->findOrFail($id));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function update(UpdateSyllabusTemplateRequest $request, string $id)
    {
      DB::beginTransaction();
      try {
          $template = $this->repository->findOrFail($id);
          $template = $this->repository->update($template, $request->validated());
          DB::commit();
          return Response::success($template);
      } catch (Exception $e) {
          DB::rollBack();
          return Response::error($e->getMessage());
      }
    }

    public function show(string $id)
    {
        try {
            return Response::success($this->repository->findOrFail($id));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
