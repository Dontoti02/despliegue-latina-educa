<?php

use Modules\Tenant\Packages\SyllabusManager\Requests\StoreSyllabusTemplateRequest;

it('defines robust rules for syllabus template creation', function () {
    $request = new StoreSyllabusTemplateRequest();

    $rules = $request->rules();

    expect($rules)->toHaveKeys(['code', 'title', 'description', 'didactic_units', 'competencies', 'competencies.*.name']);
    expect($rules['code'])->toContain('required');
    expect($rules['didactic_units'])->toContain('nullable');
    expect($rules['competencies.*.name'])->toContain('required');
});
