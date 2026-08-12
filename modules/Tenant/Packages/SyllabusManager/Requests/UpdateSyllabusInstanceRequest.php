<?php

namespace Modules\Tenant\Packages\SyllabusManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSyllabusInstanceRequest extends FormRequest
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
            'competencies' => ['required', 'array', 'min:1'],
            'competencies.*.id' => ['nullable', 'string'],
            'competencies.*.name' => ['required', 'string', 'max:255'],
            'competencies.*.description' => ['nullable', 'string'],
            'competencies.*.objective' => ['nullable', 'string'],
            'competencies.*.order' => ['required', 'integer', 'min:1'],
            'file' => ['nullable', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del syllabus es obligatorio.',
            'competencies.required' => 'Debe agregar al menos una competencia.',
            'competencies.*.name.required' => 'Cada competencia debe tener un nombre.',
            'competencies.*.order.required' => 'Cada competencia debe incluir un orden.',
        ];
    }
}
