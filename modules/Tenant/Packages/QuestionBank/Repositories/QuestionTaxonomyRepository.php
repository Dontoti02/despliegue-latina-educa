<?php

namespace Modules\Tenant\Packages\QuestionBank\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\QuestionSubject;
use Modules\Tenant\Models\QuestionTopic;
use Modules\Tenant\Packages\QuestionBank\Helpers\QuestionBankHelper;

/**
 * CRUD de las dos taxonomías del banco: Asignatura y Tema.
 *
 * Ambas comparten estructura (nombre + descripción + estado), por lo que se
 * resuelven con el mismo repositorio y un discriminador de tipo.
 */
class QuestionTaxonomyRepository
{
    const SUBJECT = 'subject';
    const TOPIC = 'topic';

    public static function list(string $type, Request $request)
    {
        QuestionBankHelper::validateReadAccess();

        $page = (int) ($request->page ?? 1);
        $size = (int) ($request->size ?? 10);

        $query = self::modelFor($type)::query()->withCount('questions');

        if ($search = trim((string) $request->search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $result = $query->orderBy('name')->paginate($size, ['*'], 'page', $page);

        return [
            'page' => $result->currentPage(),
            'size' => $result->perPage(),
            'total' => $result->total(),
            'items' => collect($result->items())->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'is_active' => $item->is_active,
                'questions_count' => $item->questions_count,
            ]),
        ];
    }

    public static function store(string $type, Request $request)
    {
        QuestionBankHelper::validateWriteAccess();

        $model = self::modelFor($type);
        $name = trim((string) $request->name);

        self::assertNameIsFree($type, $name);

        $item = $model::create([
            'name' => $name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return $item;
    }

    public static function update(string $type, Request $request, int $id)
    {
        QuestionBankHelper::validateWriteAccess();

        $item = self::modelFor($type)::find($id);

        if (!$item) {
            throw new Exception('No se encontró el registro');
        }

        $name = trim((string) $request->name);

        self::assertNameIsFree($type, $name, $id);

        $item->update([
            'name' => $name,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return $item;
    }

    public static function destroy(string $type, int $id)
    {
        QuestionBankHelper::validateWriteAccess();

        $item = self::modelFor($type)::find($id);

        if (!$item) {
            throw new Exception('No se encontró el registro');
        }

        // Se desetiqueta de forma explícita: el borrado es lógico y la cascada
        // de la FK no se dispara con soft deletes.
        $item->questions()->detach();
        $item->delete();

        return 'Registro eliminado';
    }

    private static function assertNameIsFree(string $type, string $name, ?int $ignoreId = null): void
    {
        if ($name === '') {
            throw new Exception('El nombre es obligatorio');
        }

        $query = self::modelFor($type)::where('name', $name);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $label = $type === self::SUBJECT ? 'asignatura' : 'tema';
            throw new Exception("Ya existe una $label con ese nombre");
        }
    }

    /** @return class-string<QuestionSubject|QuestionTopic> */
    private static function modelFor(string $type): string
    {
        return match ($type) {
            self::SUBJECT => QuestionSubject::class,
            self::TOPIC => QuestionTopic::class,
            default => throw new Exception('Taxonomía no soportada'),
        };
    }
}
