<?php

namespace Modules\Tenant\Packages\QuestionBank\Helpers;

use Exception;
use Illuminate\Support\Str;
use Modules\Tenant\Models\QuestionType;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\EvaluationForm\Enums\QuestionTypeEnum;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class QuestionBankHelper
{
    /**
     * Roles con permiso de escritura sobre el banco.
     *
     * La gestión del banco es responsabilidad de Secretaría Académica.
     */
    const WRITE_ROLES = [
        RolTenant::ACADEMIC_SECRETARY,
        RolTenant::ADMINISTRADOR,
    ];

    /**
     * Roles con permiso de lectura.
     *
     * El docente lee para poder insertar reactivos en sus evaluaciones y el
     * director para supervisar. El rol STUDENT queda fuera de forma deliberada:
     * las opciones del banco incluyen `is_correct`, es decir, el solucionario.
     */
    const READ_ROLES = [
        RolTenant::ACADEMIC_SECRETARY,
        RolTenant::ADMINISTRADOR,
        RolTenant::DIRECTOR,
        RolTenant::TEACHER,
    ];

    public static function validateReadAccess(): User
    {
        $user = User::authenticated();

        if (!in_array($user->rol_id, self::READ_ROLES)) {
            throw new Exception('No tiene permisos para consultar el banco de preguntas');
        }

        return $user;
    }

    public static function validateWriteAccess(): User
    {
        $user = User::authenticated();

        if (!in_array($user->rol_id, self::WRITE_ROLES)) {
            throw new Exception('Solo Secretaría Académica puede gestionar el banco de preguntas');
        }

        return $user;
    }

    /**
     * Normaliza y valida las opciones según el tipo de pregunta.
     *
     * Devuelve la estructura exacta que espera `question.options`, de modo que
     * insertar un reactivo en una evaluación sea una copia literal.
     */
    public static function normalizeOptions(string $questionTypeKey, array $options): array
    {
        $type = QuestionType::where('key', $questionTypeKey)->first();

        if (!$type) {
            throw new Exception("El tipo de pregunta '$questionTypeKey' no existe");
        }

        if (empty($options)) {
            throw new Exception('La pregunta debe tener al menos una opción');
        }

        $normalized = [];

        foreach ($options as $index => $option) {
            $label = trim((string) ($option['label'] ?? ''));

            if ($label === '') {
                throw new Exception('Todas las opciones deben tener un texto');
            }

            $item = [
                'key' => $option['key'] ?? (string) Str::uuid(),
                'label' => $label,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'is_selected' => null,
                'is_valid' => null,
            ];

            // ORDERING guarda la posición correcta dentro de la propia opción,
            // manteniendo `options` como único contenedor de datos.
            if ($questionTypeKey === QuestionTypeEnum::ORDERING) {
                $item['correct_position'] = (int) ($option['correct_position'] ?? ($index + 1));
                $item['is_correct'] = true;
            }

            // En SHORT_TEXT cada opción es una respuesta aceptada.
            if ($questionTypeKey === QuestionTypeEnum::SHORT_TEXT) {
                $item['is_correct'] = true;
            }

            $normalized[] = $item;
        }

        self::validateByType($questionTypeKey, $normalized, (bool) $type->is_gradable);

        return $normalized;
    }

    private static function validateByType(string $questionTypeKey, array $options, bool $isGradable): void
    {
        $correctCount = count(array_filter($options, fn ($o) => $o['is_correct'] === true));

        // Los tipos no calificables (ensayo, archivo, escala de opinión) no exigen
        // respuesta correcta. Hoy no hay ninguno registrado; la rama queda lista.
        if (!$isGradable) {
            return;
        }

        switch ($questionTypeKey) {
            case QuestionTypeEnum::TRUE_FALSE:
                if (count($options) !== 2) {
                    throw new Exception('Una pregunta de Verdadero/Falso debe tener exactamente dos opciones');
                }
                if ($correctCount !== 1) {
                    throw new Exception('Debe marcar exactamente una opción como correcta');
                }
                break;

            case QuestionTypeEnum::OPTION:
            case QuestionTypeEnum::DROPDOWN:
                if (count($options) < 2) {
                    throw new Exception('La pregunta debe tener al menos dos opciones');
                }
                if ($correctCount !== 1) {
                    throw new Exception('Debe marcar exactamente una opción como correcta');
                }
                break;

            case QuestionTypeEnum::OPTION_MULTIPLE:
                if (count($options) < 2) {
                    throw new Exception('La pregunta debe tener al menos dos opciones');
                }
                if ($correctCount < 1) {
                    throw new Exception('Debe marcar al menos una opción como correcta');
                }
                if ($correctCount === count($options)) {
                    throw new Exception('No todas las opciones pueden ser correctas en una selección múltiple');
                }
                break;

            case QuestionTypeEnum::SHORT_TEXT:
                if (count($options) < 1) {
                    throw new Exception('Debe registrar al menos una respuesta aceptada');
                }
                break;

            case QuestionTypeEnum::ORDERING:
                if (count($options) < 2) {
                    throw new Exception('Una pregunta de ordenamiento debe tener al menos dos elementos');
                }

                $positions = array_column($options, 'correct_position');
                sort($positions);

                if ($positions !== range(1, count($options))) {
                    throw new Exception('Las posiciones correctas deben ser consecutivas y sin repetirse');
                }
                break;

            default:
                // Tipo desconocido pero calificable: se exige la regla mínima que
                // ya aplica el motor de evaluación.
                if ($correctCount < 1) {
                    throw new Exception('Debe marcar al menos una opción como correcta');
                }
                break;
        }
    }
}
