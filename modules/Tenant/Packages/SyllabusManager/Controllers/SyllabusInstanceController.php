<?php

namespace Modules\Tenant\Packages\SyllabusManager\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\SyllabusManager\Requests\StoreSyllabusInstanceRequest;
use Modules\Tenant\Packages\SyllabusManager\Requests\UpdateSyllabusInstanceRequest;
use Modules\Tenant\Packages\SyllabusManager\UseCases\SyllabusInstanceUseCases;
use Modules\Tenant\Packages\SyllabusManager\Requests\ChangeCompetencyStatusRequest;
use Modules\Shared\Utils\Response;

class SyllabusInstanceController extends Controller
{
    public function __construct(protected SyllabusInstanceUseCases $useCases)
    {
    }


    public function changeCompetencyStatus(ChangeCompetencyStatusRequest $request, $competencyId)
    {
        try {
            $user = \Modules\Tenant\Models\User::authenticated();
            $res = $this->useCases->changeCompetencyStatus($competencyId, $request->input('new_status'), $user->id ?? null, $request->input('comment'));
            return Response::success($res);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function timeline($syllabusId)
    {
        try {
            $res = $this->useCases->timeline($syllabusId);
            return Response::success($res);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    public function showByClassroom(int $classroom_id)
    {
        return $this->useCases->showByClassroom($classroom_id);
    }

    public function templates(int $classroom_id)
    {
        return $this->useCases->templates($classroom_id);
    }

    public function store(StoreSyllabusInstanceRequest $request)
    {
        return $this->useCases->store($request);
    }

    public function update(UpdateSyllabusInstanceRequest $request, string $id)
    {
        return $this->useCases->update($request, $id);
    }
}
