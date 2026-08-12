<?php

namespace Modules\Tenant\Packages\Answer\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\Answer\UseCases\AnswerUseCases;

class AnswerController extends Controller
{
    public function list(int $content_id)
    {
        return AnswerUseCases::list($content_id);
    }

    public function delivered(int $id)
    {
        return AnswerUseCases::delivered($id);
    }
}
