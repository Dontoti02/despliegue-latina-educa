<?php

namespace Modules\Tenant\Packages\QuestionBank\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\QuestionBank\Requests\StoreQuestionBankRequest;
use Modules\Tenant\Packages\QuestionBank\Requests\UpdateQuestionBankRequest;
use Modules\Tenant\Packages\QuestionBank\UseCases\QuestionBankUseCases;

class QuestionBankController extends Controller
{
    public function params()
    {
        return QuestionBankUseCases::params();
    }

    public function list(Request $request)
    {
        return QuestionBankUseCases::list($request);
    }

    public function show(int $id)
    {
        return QuestionBankUseCases::show($id);
    }

    public function store(StoreQuestionBankRequest $request)
    {
        return QuestionBankUseCases::store($request);
    }

    public function update(UpdateQuestionBankRequest $request, int $id)
    {
        return QuestionBankUseCases::update($request, $id);
    }

    public function destroy(int $id)
    {
        return QuestionBankUseCases::destroy($id);
    }

    /** Entrega reactivos listos para insertarse en el constructor de evaluaciones. */
    public function pick(Request $request)
    {
        return QuestionBankUseCases::pick($request);
    }
}
