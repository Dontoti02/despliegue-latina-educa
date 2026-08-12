<?php

namespace Modules\Tenant\Packages\Participant\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\Participant\UseCases\ParticipantUseCases;

class ParticipantController extends Controller
{
    public function list(int $classroomId)
    {
        return ParticipantUseCases::list($classroomId);
    }
}
