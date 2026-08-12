<?php

namespace Modules\Tenant\Packages\QuestionBank\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\QuestionBank;
use Modules\Tenant\Models\QuestionSubject;
use Modules\Tenant\Models\QuestionTopic;
use Modules\Tenant\Models\QuestionType;
use Modules\Tenant\Packages\QuestionBank\Enums\QuestionDifficultyEnum;
use Modules\Tenant\Packages\QuestionBank\Helpers\QuestionBankHelper;

class QuestionBankRepository
{
    /**
     * Catálogos para poblar filtros y formularios del banco.
     */
    public static function params()
    {
        QuestionBankHelper::validateReadAccess();

        return [
            'question_types' => QuestionType::orderBy('order_number')->get(),
            'subjects' => QuestionSubject::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
            'topics' => QuestionTopic::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
            'courses' => Course::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'difficulties' => QuestionDifficultyEnum::all(),
        ];
    }

    /**
     * Listado paginado con búsqueda por enunciado y filtros por las tres
     * dimensiones de etiquetado, combinables entre sí (AND).
     */
    public static function list(Request $request)
    {
        QuestionBankHelper::validateReadAccess();

        $page = (int) ($request->page ?? 1);
        $size = (int) ($request->size ?? 10);

        $query = QuestionBank::with(['courses:id,name,code', 'subjects:id,name', 'topics:id,name', 'createdBy:id,names']);

        if ($search = trim((string) $request->search)) {
            $query->where('label', 'like', "%{$search}%");
        }

        if ($request->filled('question_type_key')) {
            $query->where('question_type_key', $request->question_type_key);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $courseIds = self::asIdArray($request->course_ids);
        if ($courseIds) {
            $query->whereHas('courses', fn ($q) => $q->whereIn('course.id', $courseIds));
        }

        $subjectIds = self::asIdArray($request->subject_ids);
        if ($subjectIds) {
            $query->whereHas('subjects', fn ($q) => $q->whereIn('question_subject.id', $subjectIds));
        }

        $topicIds = self::asIdArray($request->topic_ids);
        if ($topicIds) {
            $query->whereHas('topics', fn ($q) => $q->whereIn('question_topic.id', $topicIds));
        }

        $result = $query->orderByDesc('id')->paginate($size, ['*'], 'page', $page);

        return [
            'page' => $result->currentPage(),
            'size' => $result->perPage(),
            'total' => $result->total(),
            'items' => collect($result->items())->map(fn ($q) => self::transform($q)),
        ];
    }

    public static function show(int $id)
    {
        QuestionBankHelper::validateReadAccess();

        $question = QuestionBank::with(['courses:id,name,code', 'subjects:id,name', 'topics:id,name', 'createdBy:id,names'])
            ->find($id);

        if (!$question) {
            throw new Exception('No se encontró la pregunta en el banco');
        }

        return self::transform($question);
    }

    public static function store(Request $request)
    {
        $user = QuestionBankHelper::validateWriteAccess();

        $options = QuestionBankHelper::normalizeOptions(
            $request->question_type_key,
            $request->options ?? []
        );

        $question = QuestionBank::create([
            'uuid' => (string) Str::uuid(),
            'question_type_key' => $request->question_type_key,
            'label' => trim($request->label),
            'options' => $options,
            'score_max' => $request->score_max ?? 1,
            'difficulty' => $request->difficulty ?? QuestionDifficultyEnum::MEDIUM,
            'created_by_person_id' => $user->person_id,
            'is_active' => $request->is_active ?? true,
        ]);

        self::syncTags($question, $request);

        return self::show($question->id);
    }

    public static function update(Request $request, int $id)
    {
        QuestionBankHelper::validateWriteAccess();

        $question = QuestionBank::find($id);

        if (!$question) {
            throw new Exception('No se encontró la pregunta en el banco');
        }

        $options = QuestionBankHelper::normalizeOptions(
            $request->question_type_key,
            $request->options ?? []
        );

        // Editar un reactivo del banco NO altera las evaluaciones ya creadas:
        // al insertarse se copia (snapshot) hacia `question`, sin referencia.
        $question->update([
            'question_type_key' => $request->question_type_key,
            'label' => trim($request->label),
            'options' => $options,
            'score_max' => $request->score_max ?? 1,
            'difficulty' => $request->difficulty ?? QuestionDifficultyEnum::MEDIUM,
            'is_active' => $request->is_active ?? true,
        ]);

        self::syncTags($question, $request);

        return self::show($question->id);
    }

    public static function destroy(int $id)
    {
        QuestionBankHelper::validateWriteAccess();

        $question = QuestionBank::find($id);

        if (!$question) {
            throw new Exception('No se encontró la pregunta en el banco');
        }

        $question->courses()->detach();
        $question->subjects()->detach();
        $question->topics()->detach();
        $question->delete();

        return 'Pregunta eliminada del banco';
    }

    /**
     * Devuelve los reactivos solicitados con el formato que consume el
     * constructor de evaluaciones.
     *
     * Cada pregunta y cada opción reciben una `key` nueva: `question.key` es
     * UNIQUE global, así que reutilizar el uuid del banco rompería el guardado
     * en cuanto el reactivo se usara por segunda vez.
     */
    public static function pick(Request $request)
    {
        QuestionBankHelper::validateReadAccess();

        $ids = self::asIdArray($request->ids);

        if (!$ids) {
            throw new Exception('Debe seleccionar al menos una pregunta');
        }

        $questions = QuestionBank::whereIn('id', $ids)
            ->where('is_active', true)
            ->get();

        if ($questions->isEmpty()) {
            throw new Exception('No se encontraron preguntas activas con los identificadores enviados');
        }

        $orderStart = (int) ($request->order_start ?? 1);

        $result = $questions->values()->map(function ($question, $index) use ($orderStart) {
            $options = collect($question->options)->map(function ($option) {
                $copy = $option;
                $copy['key'] = (string) Str::uuid();
                $copy['is_selected'] = null;
                $copy['is_valid'] = null;

                return $copy;
            })->all();

            return [
                'id' => null,
                'form_id' => null,
                'key' => (string) Str::uuid(),
                'question_type_key' => $question->question_type_key,
                'label' => $question->label,
                'order_number' => $orderStart + $index,
                'score' => null,
                'score_max' => $question->score_max,
                'options' => $options,
                'bank_question_id' => $question->id,
            ];
        });

        QuestionBank::whereIn('id', $questions->pluck('id'))->increment('usage_count');

        return $result;
    }

    private static function syncTags(QuestionBank $question, Request $request): void
    {
        $question->courses()->sync(self::asIdArray($request->course_ids));
        $question->subjects()->sync(self::asIdArray($request->subject_ids));
        $question->topics()->sync(self::asIdArray($request->topic_ids));
    }

    private static function asIdArray($value): array
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        $values = is_array($value) ? $value : explode(',', (string) $value);

        return collect($values)
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();
    }

    private static function transform(QuestionBank $question): array
    {
        return [
            'id' => $question->id,
            'uuid' => $question->uuid,
            'question_type_key' => $question->question_type_key,
            'label' => $question->label,
            'options' => $question->options,
            'score_max' => $question->score_max,
            'difficulty' => $question->difficulty,
            'is_active' => $question->is_active,
            'usage_count' => $question->usage_count,
            'courses' => $question->courses->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
            ])->values(),
            'subjects' => $question->subjects->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values(),
            'topics' => $question->topics->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
            ])->values(),
            'created_by' => $question->createdBy?->names,
            'created_at' => $question->created_at,
        ];
    }
}
