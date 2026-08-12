<?php

namespace Modules\Tenant\Packages\SyllabusManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSyllabusTemplateRequest extends FormRequest
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
            'didactic_units' => ['nullable', 'array'],
            'didactic_units.*' => ['nullable', 'integer', 'exists:course,id'],
            'competencies' => ['nullable', 'array'],
            'competencies.*.name' => ['required', 'string', 'max:255'],
            'competencies.*.description' => ['nullable', 'string', 'max:1000'],
            'competencies.*.rich_content' => ['nullable', 'string'],
            'competencies.*.objective' => ['nullable', 'string', 'max:1000'],
            'competencies.*.sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título de la plantilla es obligatorio.',
            'competencies.*.name.required' => 'Cada competencia debe tener un nombre.',
            'competencies.*.name.max' => 'El nombre de la competencia no puede exceder 255 caracteres.',
            'didactic_units.*.exists' => 'Una de las unidades didácticas seleccionadas no existe.',
        ];
    }
}
