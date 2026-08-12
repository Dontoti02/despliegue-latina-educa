<?php

namespace Modules\Tenant\Packages\Participant\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Participant\Repositories\ParticipantRepository;

class ParticipantUseCases
{
    public static function list(int $classroomId)
    {
        try {
            $result = ParticipantRepository::list($classroomId);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
