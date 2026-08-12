<?php

namespace Modules\Tenant\Packages\Training\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Training\Repositories\TrainingParticipantRepository;

class TrainingParticipantUseCases
{
    public static function list(int $training_id)
    {
        try {
            $result = TrainingParticipantRepository::list($training_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
