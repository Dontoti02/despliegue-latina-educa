<?php

namespace Modules\Tenant\Packages\EvaluationForm\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Packages\EvaluationForm\Enums\QuestionTypeEnum;
use Modules\Tenant\Packages\EvaluationForm\Helpers\EvaluationFormHelper;
use Modules\Tenant\Models\Form;
use Modules\Tenant\Models\QuestionType;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Answer\Enums\AnswerStatus;
use Modules\Tenant\Packages\Average\Repositories\AverageRepository;
use Modules\Tenant\Packages\Content\Repositories\ContentRepository;

class EvaluationFormRepository
{
    public static function questionTypes()
    {
        $questionTypes = QuestionType::select()
            ->orderBy('order_number', 'asc')
            ->get();

        return $questionTypes;
    }

    public static function set(Content $content, $request, bool $isUpdate)
    {
        EvaluationFormHelper::validateRequest($request, $isUpdate);
        $request = (object) $request;

        $scoreMax = $request->score_max;
        $scoreMaxQuestions = array_sum(array_column($request->questions, 'score_max'));

        if ($content->score != $scoreMax) {
            throw new Exception("El puntaje máximo del formulario no coincide con el puntaje máximo de la evaluación");
        }

        if ($scoreMax != $scoreMaxQuestions) {
            throw new Exception("La suma de los puntajes de las preguntas no coincide con el puntaje máximo del formulario");
        }

        $form = Form::select()
            ->where('content_id', $content->id)
            ->first();

        if ($form) {
            $isModified = $form->title != $request->title ||
                $form->description != $request->description ||
                $form->score_max != $request->score_max;

            $questionKeysRequest = array_column($request->questions, 'key');
            $questionKeysForm = $form->questions->pluck('key')->toArray();
            $isModified |= count(array_diff($questionKeysRequest, $questionKeysForm)) > 0 ||
                count(array_diff($questionKeysForm, $questionKeysRequest)) > 0;

            if (!$isModified) {
                foreach ($request->questions as $questionRequest) {
                    $questionForm = $form->questions->where('key', $questionRequest['key'])->first();
                    if ($questionForm) {
                        $isModified |= $questionForm->question_type_key != $questionRequest['question_type_key'] ||
                            $questionForm->label != $questionRequest['label'] ||
                            $questionForm->order_number != $questionRequest['order_number'] ||
                            $questionForm->score_max != $questionRequest['score_max'] ||
                            json_encode($questionForm->options) != json_encode($questionRequest['options']);
                    } else {
                        $isModified = true;
                    }

                    if ($isModified) {
                        break;
                    }
                }
            }

            if ($isModified && $form->responses()->exists()) {
                throw new Exception("El formulario de evaluación ya tiene respuestas");
            }

            $form->update([
                'title' => $request->title,
                'description' => $request->description,
                'score_max' => $request->score_max,
            ]);

            $questionKeys = array_column($request->questions, 'key');
            $form->questions()->whereNotIn('key', $questionKeys)->delete();
        } else {
            $form = Form::create([
                'content_id' => $content->id,
                'uuid' => $request->uuid,
                'title' => $request->title,
                'description' => $request->description,
                'score_max' => $request->score_max,
            ]);
        }

        $questionTypes = QuestionType::all()->keyBy('key');

        foreach ($request->questions as $question) {
            $label = $question['label'];
            $options = $question['options'];

            $type = $questionTypes[$question['question_type_key']] ?? null;
            $isGradable = $type ? (bool) $type->is_gradable : true;

            // Los tipos no calificables (ensayo, archivo, escala de opinión) no
            // exigen respuesta correcta porque los puntúa el docente a mano.
            if ($isGradable) {
                $valid = array_filter($options, function ($option) {
                    return isset($option['is_correct']) && $option['is_correct'] === true;
                });

                if (empty($valid)) {
                    throw new Exception("En la pregunta $label al menos una opción debe ser la correcta");
                }
            }

            $existingQuestion = $form->questions()->where('key', $question['key'])->first();

            if ($existingQuestion) {
                $existingQuestion->update([
                    'question_type_key' => $question['question_type_key'],
                    'label' => $question['label'],
                    'order_number' => $question['order_number'],
                    'score_max' => $question['score_max'],
                    'options' => $options,
                ]);
            } else {
                $form->questions()->create([
                    'question_type_key' => $question['question_type_key'],
                    'key' => $question['key'],
                    'label' => $question['label'],
                    'order_number' => $question['order_number'],
                    'score_max' => $question['score_max'],
                    'options' => $options,
                ]);
            }
        }
    }

    public static function delete(Content $content)
    {
        $form = $content->form;

        if ($form->responses()->exists()) {
            throw new Exception("El formulario de evaluación ya tiene respuestas");
        }

        $form->questions()->delete();
        $form->delete();

        $content->update([
            'has_evaluation_form' => false,
        ]);
    }

    public static function get(string $uuid, int $person_id)
    {
        $user = User::authenticated();
        $is_student = $user->rol_id === RolTenant::STUDENT;

        $form = Form::select()
            ->where('uuid', $uuid)
            ->with('questions')
            ->first();

        if (!$form) {
            throw new Exception("No se encontró el formulario de evaluación");
        }

        $content = $form->content;
        $classroom_id = $content->contentGroup->classroom_id;

        if (!$is_student) {
            if ($person_id === 0) {
                throw new Exception('El parámetro person_id es requerido');
            }

            $user = User::byKey('person_id', $person_id);
        }

        ClassroomHelper::validateAccess($classroom_id, 'student');

        ContentRepository::manageStates($content);

        if (!$content->is_visible) {
            throw new Exception("El contenido no está disponible");
        }

        $response = $form->responses()
            ->where('person_id', $user->person_id)
            ->first();

        $result = [
            'id' => $form->id,
            'content_id' => $form->content_id,
            'uuid' => $form->uuid,
            'title' => $form->title,
            'description' => $form->description,
            'score_max' => $form->score_max,
            'is_open' => $content->is_open,
        ];

        if ($response) {
            $result['is_pending'] = false;
            $result['score'] = $response->score;

            if (!$is_student) {
                $result['questions'] = $response->questions;
            }
        } else {
            $result['is_pending'] = true;
            $result['score'] = 0;

            if ($is_student) {
                $questions = $form->questions->toArray();
                foreach ($questions as &$question) {
                    // En SHORT_TEXT las opciones SON las respuestas aceptadas:
                    // enviarlas al alumno entregaría el solucionario completo.
                    if ($question['question_type_key'] === QuestionTypeEnum::SHORT_TEXT) {
                        $question['options'] = [];
                        continue;
                    }

                    foreach ($question['options'] as &$option) {
                        unset($option['is_correct']);
                        // `correct_position` es la respuesta de ORDERING.
                        unset($option['correct_position']);
                    }
                    unset($option);

                    if ($question['question_type_key'] === QuestionTypeEnum::ORDERING) {
                        shuffle($question['options']);
                    }
                }
                unset($question);

                $result['questions'] = $questions;
            }
        }

        return $result;
    }

    public static function delivered(Request $request)
    {
        $user = User::authenticated();

        EvaluationFormHelper::validateDeliveredRequest($request);

        $form = Form::select()
            ->where('uuid', $request->uuid)
            ->first();

        if (!$form) {
            throw new Exception("No se encontró el formulario de evaluación");
        }

        $content = $form->content;
        $classroom_id = $content->contentGroup->classroom_id;

        ClassroomHelper::validateAccess($classroom_id, 'student');

        $date = Carbon::now();
        $date_start = Carbon::parse($content->date_start);
        $date_limit = Carbon::parse($content->date_limit);

        if (!$content->is_open && ($date < $date_start || $date > $date_limit)) {
            throw new Exception('La tarea no admite respuestas');
        }

        $response = $form->responses()
            ->where('person_id', $user->person_id)
            ->first();

        if ($response) {
            $result = [
                'message' => "Ya has respondido este formulario de evaluación",
                'score' => $response->score,
                'total' => $form->score_max,
            ];

            return $result;
        }

        $score = 0;
        $hasManualGrading = false;

        $questions = [];
        $questionsRequest = collect($request->questions)->keyBy('key');
        $questionTypes = QuestionType::all()->keyBy('key');

        foreach ($form->questions as $question) {
            $questionRequest = $questionsRequest[$question->key] ?? null;
            if (!$questionRequest) {
                throw new Exception("No se encontró la pregunta: $question->label");
            }

            $type = $questionTypes[$question->question_type_key] ?? null;
            $isGradable = $type ? (bool) $type->is_gradable : true;

            if (!$isGradable) {
                $hasManualGrading = true;
            }

            // Los tipos históricos (OPTION, OPTION_MULTIPLE) y los nuevos basados
            // en opciones caen en `default`, conservando la lógica original.
            switch ($question->question_type_key) {
                case QuestionTypeEnum::SHORT_TEXT:
                    [$questionScore, $options, $answerText] =
                        self::gradeShortText($question, $questionRequest, $isGradable);
                    break;

                case QuestionTypeEnum::ORDERING:
                    [$questionScore, $options, $answerText] =
                        self::gradeOrdering($question, $questionRequest, $isGradable);
                    break;

                default:
                    [$questionScore, $options, $answerText] =
                        self::gradeOptionBased($question, $questionRequest, $isGradable);
                    break;
            }

            $score += $questionScore;

            $entry = [
                'id' => $question->id,
                'form_id' => $question->form_id,
                'question_type_key' => $question->question_type_key,
                'key' => $question->key,
                'label' => $question->label,
                'order_number' => $question->order_number,
                'score' => $isGradable ? $questionScore : null,
                'score_max' => $question->score_max,
                'options' => $options,
            ];

            if (!is_null($answerText)) {
                $entry['answer_text'] = $answerText;
            }

            $questions[] = $entry;
        }

        $form->responses()->create([
            'person_id' => $user->person_id,
            'questions' => $questions,
            'score' => $score,
        ]);

        $answer = $content->answers()
            ->where('answer.person_id', $user->person_id)
            ->first();

        // Si el formulario incluye preguntas no calificables, la nota todavía no
        // es definitiva: queda entregada a la espera de la revisión del docente y
        // el promedio no se recalcula aún.
        $answer->update([
            'status' => $hasManualGrading ? AnswerStatus::DELIVERED : AnswerStatus::EVALUATED,
            'score' => $score,
        ]);

        if ($hasManualGrading) {
            return "¡Formulario entregado! Contiene preguntas que revisará el docente.";
        }

        AverageRepository::calculateAverageDetail($classroom_id, $answer->person_id);

        return "¡Formulario de evaluación entregado y evaluado!";
    }

    /**
     * Calificación genérica por opciones: la lógica histórica de OPTION y
     * OPTION_MULTIPLE, más los tipos nuevos que se apoyan en `is_correct`
     * (TRUE_FALSE, DROPDOWN).
     *
     * @return array{0: float, 1: array, 2: ?string}
     */
    private static function gradeOptionBased($question, array $questionRequest, bool $isGradable): array
    {
        $optionTotalCorrect = 0;
        $optionMarkedCorrect = 0;
        $optionMarkedIncorrect = 0;

        $options = [];
        $optionsRequest = collect($questionRequest['options'] ?? [])->keyBy('key');

        foreach ($question->options as $option) {
            $option = (object) $option;

            $optionRequest = $optionsRequest[$option->key] ?? null;
            if (!$optionRequest) {
                throw new Exception("No se encontró la opción: $option->label");
            }

            $is_correct = $option->is_correct;
            $is_selected = $optionRequest['is_selected'];
            $is_valid = $is_correct == true && $is_selected == true;

            if ($is_correct) $optionTotalCorrect++;
            if ($is_valid) $optionMarkedCorrect++;
            if (!$is_correct && $is_selected == true) $optionMarkedIncorrect++;

            $options[] = [
                'key' => $option->key,
                'label' => $option->label,
                'is_correct' => $is_correct,
                'is_selected' => $is_selected,
                'is_valid' => $is_valid,
            ];
        }

        if (!$isGradable || $optionTotalCorrect === 0) {
            return [0.0, $options, null];
        }

        // Se descuentan las opciones incorrectas marcadas. Sin este ajuste, en
        // OPTION_MULTIPLE marcar todas las alternativas otorgaba el puntaje
        // completo. En los tipos de opción única el resultado es idéntico al
        // anterior: acertar da 1/1 y fallar da 0.
        $net = max($optionMarkedCorrect - $optionMarkedIncorrect, 0);
        $questionScore = round(($net / $optionTotalCorrect) * $question->score_max, 2);

        return [$questionScore, $options, null];
    }

    /**
     * Respuesta corta: `options` contiene las respuestas aceptadas y el alumno
     * envía su texto en `answer_text`. Se califica todo o nada.
     *
     * @return array{0: float, 1: array, 2: ?string}
     */
    private static function gradeShortText($question, array $questionRequest, bool $isGradable): array
    {
        $answerText = trim((string) ($questionRequest['answer_text'] ?? ''));
        $normalizedAnswer = self::normalizeText($answerText);

        $matched = false;
        $options = [];

        foreach ($question->options as $option) {
            $option = (object) $option;

            $isMatch = $normalizedAnswer !== ''
                && self::normalizeText($option->label) === $normalizedAnswer;

            if ($isMatch) $matched = true;

            $options[] = [
                'key' => $option->key,
                'label' => $option->label,
                'is_correct' => $option->is_correct,
                'is_selected' => $isMatch,
                'is_valid' => $isMatch,
            ];
        }

        $questionScore = ($isGradable && $matched)
            ? round((float) $question->score_max, 2)
            : 0.0;

        return [$questionScore, $options, $answerText];
    }

    /**
     * Ordenar secuencia: cada opción guarda `correct_position` y el alumno envía
     * `selected_position`. El puntaje es proporcional a los aciertos de posición.
     *
     * @return array{0: float, 1: array, 2: ?string}
     */
    private static function gradeOrdering($question, array $questionRequest, bool $isGradable): array
    {
        $optionsRequest = collect($questionRequest['options'] ?? [])->keyBy('key');

        $options = [];
        $totalItems = 0;
        $correctItems = 0;

        foreach ($question->options as $option) {
            $option = (object) $option;

            $optionRequest = $optionsRequest[$option->key] ?? null;
            if (!$optionRequest) {
                throw new Exception("No se encontró la opción: $option->label");
            }

            $correctPosition = (int) ($option->correct_position ?? 0);
            $selectedPosition = (int) ($optionRequest['selected_position'] ?? 0);
            $isValid = $correctPosition > 0 && $correctPosition === $selectedPosition;

            $totalItems++;
            if ($isValid) $correctItems++;

            $options[] = [
                'key' => $option->key,
                'label' => $option->label,
                'is_correct' => true,
                'correct_position' => $correctPosition,
                'selected_position' => $selectedPosition,
                'is_selected' => $selectedPosition > 0,
                'is_valid' => $isValid,
            ];
        }

        if (!$isGradable || $totalItems === 0) {
            return [0.0, $options, null];
        }

        $questionScore = round(($correctItems / $totalItems) * $question->score_max, 2);

        return [$questionScore, $options, null];
    }

    /**
     * Normaliza texto para comparar respuestas cortas: sin mayúsculas, sin
     * tildes y sin espacios redundantes.
     */
    private static function normalizeText(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));

        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);

        return preg_replace('/\s+/', ' ', $value);
    }
}
