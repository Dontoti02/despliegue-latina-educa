<?php

namespace Modules\Tenant\Packages\Room\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Room\Helpers\RoomTypeHelper;
use Modules\Tenant\Models\RoomType;

class RoomTypeRepository
{
    public static function list(Request $request)
    {
        RoomTypeHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $roomTypes = RoomType::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->withCount('rooms')
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $roomTypesMap = [];
        foreach ($roomTypes->items() as $roomType) {
            $roomTypesMap[] = [
                'id' => $roomType->id,
                'name' => $roomType->name,
                'total_rooms' => $roomType->rooms_count,
            ];
        }

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $roomTypes->total(),
            'items' => $roomTypesMap,
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        RoomTypeHelper::validateRequest($request);

        $name = $request->input('name');

        $existsRoomTypeName = RoomType::select()
            ->where('name', $name)
            ->exists();

        if ($existsRoomTypeName) {
            throw new Exception('El nombre del tipo de ambiente ya existe.');
        }

        RoomType::create([
            'name' => $name,
        ]);

        return 'Tipo de ambiente creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        RoomTypeHelper::validateRequest($request);

        $name = $request->input('name');

        $roomType = RoomType::findOrFail($id);

        $existsRoomTypeName = RoomType::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsRoomTypeName) {
            throw new Exception('El nombre del tipo de ambiente ya existe.');
        }

        $roomType->update([
            'name' => $name,
        ]);

        return 'Tipo de ambiente actualizado correctamente.';
    }

    public static function delete(int $id)
    {
        $roomType = RoomType::findOrFail($id);

        if ($roomType->rooms()->exists()) {
            throw new Exception('No se puede eliminar el tipo de ambiente porque tiene ambientes asociados.');
        }

        $roomType->delete();

        return 'Tipo de ambiente eliminado correctamente.';
    }
}
