<?php

namespace Modules\Tenant\Packages\EvaluationForm\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationFormHelper
{
    public static function validateRequest($request, $isUpdate)
    {
        $validation = $isUpdate ? "exists" : "unique";

        $validator = Validator::make($request, [
            "uuid" => "required|string|$validation:form,uuid",
            "title" => "required|string",
            "description" => "nullable|string",
            "score_max" => "required|numeric|between:0,20",

            "questions" => "required|array|min:1",

            "questions.*.key" => "required|string",
            "questions.*.label" => "required|string",
            "questions.*.question_type_key" => "required|string|exists:question_type,key",
            "questions.*.order_number" => "required|integer|min:1",
            "questions.*.score_max" => "required|numeric|between:0,20",

            // Se validarán según el tipo de pregunta
            "questions.*.options" => "sometimes|array",
            "questions.*.options.*.key" => "sometimes|required|string",
            "questions.*.options.*.label" => "sometimes|required|string",
            "questions.*.options.*.is_correct" => "sometimes|required|boolean",

            "questions.*.answer_text" => "nullable|string",
        ]);

        $validator->after(function ($validator) use ($request) {

            foreach ($request['questions'] as $index => $question) {

                $type = $question['question_type_key'];

                if ($type === 'SHORT_TEXT') {

                    if (empty(trim($question['answer_text'] ?? ''))) {
                        $validator->errors()->add(
                            "questions.$index.answer_text",
                            "La respuesta es obligatoria."
                        );
                    }

                    continue;
                }

                if (
                    !isset($question['options']) ||
                    !is_array($question['options']) ||
                    count($question['options']) < 2
                ) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        "Debe registrar al menos dos opciones."
                    );
                }
            }
        });

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDeliveredRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [

            "uuid" => "required|string|exists:form,uuid",

            "questions" => "required|array|min:1",

            "questions.*.key" => "required|string|exists:question,key",
            "questions.*.label" => "required|string",
            "questions.*.question_type_key" => "required|string|exists:question_type,key",
            "questions.*.order_number" => "required|integer|min:1",
            "questions.*.score_max" => "required|numeric|between:0,20",

            // Opcionales, se validan según el tipo
            "questions.*.options" => "sometimes|array",
            "questions.*.options.*.key" => "sometimes|required|string",
            "questions.*.options.*.label" => "sometimes|required|string",
            "questions.*.options.*.is_selected" => "sometimes|required|boolean",

            "questions.*.answer_text" => "nullable|string",
        ]);

        $validator->after(function ($validator) use ($request) {

            foreach ($request->input('questions', []) as $index => $question) {

                $type = $question['question_type_key'];

                // Preguntas de texto corto
                if ($type === 'SHORT_TEXT') {

                    if (empty(trim($question['answer_text'] ?? ''))) {
                        $validator->errors()->add(
                            "questions.$index.answer_text",
                            "La respuesta es obligatoria."
                        );
                    }

                    continue;
                }

                // Para el resto de tipos las opciones son obligatorias
                if (
                    !isset($question['options']) ||
                    !is_array($question['options']) ||
                    count($question['options']) < 2
                ) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        "Cada pregunta debe tener al menos dos opciones."
                    );
                    continue;
                }

                // Debe existir al menos una opción seleccionada
                $selectedOptions = array_filter(
                    $question['options'],
                    fn($option) => $option['is_selected'] ?? false
                );

                if (count($selectedOptions) < 1) {
                    $validator->errors()->add(
                        "questions.$index.options",
                        "Cada pregunta debe tener al menos una opción seleccionada."
                    );
                }
            }
        });

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}