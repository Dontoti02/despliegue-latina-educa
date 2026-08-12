<?php

use Modules\Tenant\Models\SyllabusInstance;
use Modules\Tenant\Models\SyllabusInstanceCompetency;
use Modules\Tenant\Packages\SyllabusManager\Repositories\SyllabusInstanceRepository;

it('builds a syllabus summary with completion percentage', function () {
    $repository = new SyllabusInstanceRepository();

    $instance = new SyllabusInstance([
        'id' => 'syllabus-1',
        'title' => 'Syllabus de prueba',
        'created_at' => now(),
    ]);

    $instance->setRelation('competencies', collect([
        new SyllabusInstanceCompetency([
            'id' => 'comp-1',
            'name' => 'Competencia 1',
            'description' => 'Descripción 1',
            'objective' => 'Objetivo 1',
            'status' => 'completed',
            'sort_order' => 1,
        ]),
        new SyllabusInstanceCompetency([
            'id' => 'comp-2',
            'name' => 'Competencia 2',
            'description' => 'Descripción 2',
            'objective' => 'Objetivo 2',
            'status' => 'in_progress',
            'sort_order' => 2,
        ]),
    ]));

    $summary = $repository->buildSyllabusSummaryForResponse($instance);

    expect($summary)->not->toBeNull()
        ->and($summary['title'])->toBe('Syllabus de prueba')
        ->and($summary['total_competencies'])->toBe(2)
        ->and($summary['completed_competencies'])->toBe(1)
        ->and($summary['completion_percentage'])->toBe(50);
});
