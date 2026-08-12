<?php

namespace Modules\Tenant\Packages\EvaluationForm\Enums;

/**
 * Claves del catálogo `question_type`.
 *
 * OPTION y OPTION_MULTIPLE son los tipos históricos y se califican con la lógica
 * genérica de opciones. Los tipos nuevos se resuelven en ramas dedicadas del
 * motor, dejando la lógica original intacta como caso `default`.
 */
class QuestionTypeEnum
{
    /** Opción única (radio). */
    const OPTION = 'OPTION';

    /** Selección múltiple (checkbox). */
    const OPTION_MULTIPLE = 'OPTION_MULTIPLE';

    /** Verdadero / Falso: opción única con exactamente dos alternativas. */
    const TRUE_FALSE = 'TRUE_FALSE';

    /** Lista desplegable: misma data que OPTION, distinto render. */
    const DROPDOWN = 'DROPDOWN';

    /** Respuesta corta: `options` contiene las respuestas aceptadas. */
    const SHORT_TEXT = 'SHORT_TEXT';

    /** Ordenar secuencia: cada opción lleva `correct_position`. */
    const ORDERING = 'ORDERING';

    /**
     * Tipos que se comportan como "una sola opción correcta marcada por el alumno".
     */
    public static function singleChoice(): array
    {
        return [
            self::OPTION,
            self::TRUE_FALSE,
            self::DROPDOWN,
        ];
    }

    /**
     * Tipos cuya calificación usa la lógica genérica de opciones (`is_correct` +
     * `is_selected`). Todo lo que no esté aquí tiene rama propia en el motor.
     */
    public static function optionBased(): array
    {
        return array_merge(self::singleChoice(), [self::OPTION_MULTIPLE]);
    }

    public static function all(): array
    {
        return array_merge(self::optionBased(), [
            self::SHORT_TEXT,
            self::ORDERING,
        ]);
    }
}
