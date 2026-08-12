<?php

namespace Modules\Tenant\Packages\QuestionBank\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\QuestionBank\Repositories\QuestionTaxonomyRepository;
use Modules\Tenant\Packages\QuestionBank\Requests\StoreTaxonomyRequest;
use Modules\Tenant\Packages\QuestionBank\UseCases\QuestionTaxonomyUseCases;

class QuestionTaxonomyController extends Controller
{
    // --- Asignaturas ---

    public function listSubjects(Request $request)
    {
        return QuestionTaxonomyUseCases::list(QuestionTaxonomyRepository::SUBJECT, $request);
    }

    public function storeSubject(StoreTaxonomyRequest $request)
    {
        return QuestionTaxonomyUseCases::store(QuestionTaxonomyRepository::SUBJECT, $request);
    }

    public function updateSubject(StoreTaxonomyRequest $request, int $id)
    {
        return QuestionTaxonomyUseCases::update(QuestionTaxonomyRepository::SUBJECT, $request, $id);
    }

    public function destroySubject(int $id)
    {
        return QuestionTaxonomyUseCases::destroy(QuestionTaxonomyRepository::SUBJECT, $id);
    }

    // --- Temas ---

    public function listTopics(Request $request)
    {
        return QuestionTaxonomyUseCases::list(QuestionTaxonomyRepository::TOPIC, $request);
    }

    public function storeTopic(StoreTaxonomyRequest $request)
    {
        return QuestionTaxonomyUseCases::store(QuestionTaxonomyRepository::TOPIC, $request);
    }

    public function updateTopic(StoreTaxonomyRequest $request, int $id)
    {
        return QuestionTaxonomyUseCases::update(QuestionTaxonomyRepository::TOPIC, $request, $id);
    }

    public function destroyTopic(int $id)
    {
        return QuestionTaxonomyUseCases::destroy(QuestionTaxonomyRepository::TOPIC, $id);
    }
}
