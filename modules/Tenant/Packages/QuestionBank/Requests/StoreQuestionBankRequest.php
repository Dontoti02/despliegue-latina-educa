<?php

namespace Modules\Tenant\Packages\QuestionBank\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Tenant\Packages\QuestionBank\Enums\QuestionDifficultyEnum;

class StoreQuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string'],
            'question_type_key' => ['required', 'string', 'exists:question_type,key'],
            'score_max' => ['nullable', 'numeric', 'min:0.01', 'max:99.99'],
            'difficulty' => ['nullable', 'string', 'in:' . implode(',', QuestionDifficultyEnum::all())],
            'is_active' => ['nullable', 'boolean'],

            'options' => ['required', 'array', 'min:1'],
            'options.*.key' => ['nullable', 'string'],
            'options.*.label' => ['required', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
            'options.*.correct_position' => ['nullable', 'integer', 'min:1'],

            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:course,id'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:question_subject,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['integer', 'exists:question_topic,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El enunciado de la pregunta es obligatorio.',
            'question_type_key.required' => 'Debe seleccionar el tipo de pregunta.',
            'question_type_key.exists' => 'El tipo de pregunta seleccionado no existe.',
            'score_max.max' => 'El puntaje máximo no puede superar 99.99.',
            'options.required' => 'La pregunta debe tener al menos una opción.',
            'options.*.label.required' => 'Todas las opciones deben tener un texto.',
            'course_ids.*.exists' => 'Una de las unidades didácticas seleccionadas no existe.',
            'subject_ids.*.exists' => 'Una de las asignaturas seleccionadas no existe.',
            'topic_ids.*.exists' => 'Uno de los temas seleccionados no existe.',
        ];
    }
}
