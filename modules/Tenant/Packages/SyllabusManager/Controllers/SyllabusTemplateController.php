<?php

namespace Modules\Tenant\Packages\SyllabusManager\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\SyllabusManager\Requests\StoreSyllabusTemplateRequest;
use Modules\Tenant\Packages\SyllabusManager\Requests\UpdateSyllabusTemplateRequest;
use Modules\Tenant\Packages\SyllabusManager\UseCases\SyllabusTemplateUseCases;

class SyllabusTemplateController extends Controller
{
    public function __construct(protected SyllabusTemplateUseCases $useCases)
    {
    }

    public function index(Request $request)
    {
        return $this->useCases->index($request);
    }

    public function create()
    {
        return response()->json(['success' => true, 'message' => 'Formulario de creación listo.']);
    }

    public function store(StoreSyllabusTemplateRequest $request)
    {
        return $this->useCases->store($request);
    }

    public function edit(string $id)
    {
        return $this->useCases->edit($id);
    }

    public function update(UpdateSyllabusTemplateRequest $request, string $id)
    {
        return $this->useCases->update($request, $id);
    }

    public function show(string $id)
    {
        return $this->useCases->show($id);
    }
}
