<?php

namespace Modules\Admin\Helpers;

use Illuminate\Support\Collection;

class ReadjustmentHelper
{
    public static function getBestStudyPlanByStudyProgram(Collection $studyPlans)
    {
        // Función para extraer año
        $extractYear = function ($name) {
            return preg_match('/\b(19|20)\d{2}\b/', $name, $m)
                ? (int) $m[0]
                : null;
        };

        // Ordenar usando Collection (mejor que usort aquí)
        $sorted = $studyPlans->sort(function ($a, $b) use ($extractYear) {
            $yearA = $extractYear($a->name);
            $yearB = $extractYear($b->name);

            // Caso 1: ambos tienen año → mayor primero
            if ($yearA !== null && $yearB !== null) {
                return $yearB <=> $yearA;
            }

            // Caso 2: solo uno tiene año → ese gana
            if ($yearA !== null) return -1;
            if ($yearB !== null) return 1;

            // Caso 3: ninguno tiene año → orden alfabético
            return strcasecmp($a->name, $b->name);
        });

        return $sorted->first();
    }
}
