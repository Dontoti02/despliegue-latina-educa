<?php

namespace Modules\Tenant\Packages\SyllabusManager\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeCompetencyStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_status' => ['required', 'string', 'in:not_started,in_progress,completed'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
