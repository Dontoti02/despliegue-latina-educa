<?php

namespace Modules\Tenant\Packages\SyllabusManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSyllabusInstanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'classroom_id' => ['required', 'integer', 'exists:classroom,id'],
            'competencies' => ['required', 'array', 'min:1'],
            'competencies.*.id' => ['nullable', 'string'],
            'competencies.*.name' => ['required', 'string', 'max:255'],
            'competencies.*.description' => ['nullable', 'string'],
            'competencies.*.objective' => ['nullable', 'string'],
            'competencies.*.order' => ['required', 'integer', 'min:1'],
            'competencies.*.status' => ['required', 'string', 'in:pending,in_progress,completed'],
            'file' => ['nullable', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del syllabus es obligatorio.',
            'classroom_id.required' => 'El id del aula es obligatorio.',
            'classroom_id.exists' => 'El aula no existe.',
            'competencies.required' => 'Debe agregar al menos una competencia.',
            'competencies.*.name.required' => 'Cada competencia debe tener un nombre.',
            'competencies.*.order.required' => 'Cada competencia debe incluir un orden.',
            'competencies.*.status.in' => 'El estado de la competencia no es válido.',
        ];
    }
}
