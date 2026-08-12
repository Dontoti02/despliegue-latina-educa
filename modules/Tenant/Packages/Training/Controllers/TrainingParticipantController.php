<?php

namespace Modules\Tenant\Packages\Training\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\Training\UseCases\TrainingParticipantUseCases;

class TrainingParticipantController extends Controller
{
    public function list(int $training_id)
    {
        return TrainingParticipantUseCases::list($training_id);
    }
}
