<?php

namespace Modules\Tenant\Packages\SyllabusManager\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\SyllabusManager\Repositories\SyllabusInstanceRepository;
use Modules\Tenant\Packages\SyllabusManager\Requests\StoreSyllabusInstanceRequest;
use Modules\Tenant\Packages\SyllabusManager\Requests\UpdateSyllabusInstanceRequest;

class SyllabusInstanceUseCases
{
    public function __construct(protected SyllabusInstanceRepository $repository)
    {
    } 

    public function showByClassroom(int $classroomId)
    {
        try {
            $instance = $this->repository->findByClassroomId($classroomId);
            return Response::success($this->repository->transformInstance($instance));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function changeCompetencyStatus(string $competencyId, string $newStatus, ?int $changedBy = null, ?string $comment = null)
    {
        try {
            return $this->repository->recordCompetencyStatusChange($competencyId, $newStatus, $changedBy, $comment);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function timeline(string $syllabusId)
    {
        try {
            return $this->repository->getTimelineForSyllabus($syllabusId);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function templates(int $classroomId)
    {
        try {
            $templates = $this->repository->listTemplatesForClassroom($classroomId);
            return Response::success(['data' => $templates]);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public function store(StoreSyllabusInstanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $data['file'] = $request->file('file');
            $instance = $this->repository->create($data);
            Log::alert("instance:". json_encode($instance));
            DB::commit();
            return Response::success($this->repository->transformInstance($instance));
        } catch (Exception $e) {
            Log::error("error". $e->getMessage());
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public function update(UpdateSyllabusInstanceRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $instance = $this->repository->findById($id);
            $data = $request->validated();
            $data['file'] = $request->file('file');
            $instance = $this->repository->update($instance, $data);
            DB::commit();
            return Response::success($this->repository->transformInstance($instance));
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
