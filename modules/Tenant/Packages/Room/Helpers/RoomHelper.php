<?php

namespace Modules\Tenant\Packages\Room\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Room;
use Modules\Tenant\Models\RoomReserve;

class RoomHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "search"            => "nullable|string",
            "capacity"          => "nullable|integer|gt:0",
            "room_type_id"      => "nullable|integer|exists:room_type,id",
            "date_available"    => "nullable|date",
            "only_available"    => "required|boolean"
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadImageRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'  => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name"          => "required|string",
            "location"      => "required|string",
            "room_type_id"  => "required|integer|exists:room_type,id",
            "capacity"      => "required|integer|gt:0",
            "is_active"     => "required|boolean",
            "image"         => "nullable|string"
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListReservesRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"          => "required|integer|gt:0",
            "size"          => "required|integer|gt:0",
            "room_id"       => "required|integer|exists:room,id",
            "date"          => "required|date",
            "is_pending"    => "required|boolean",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAddReserveRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "room_id"       => "required|integer|exists:room,id",
            "dates"         => "required|array",
            "dates.*"       => "required|date",
            "hour_start"    => "required|date_format:H:i",
            "hour_end"      => "required|date_format:H:i",
            "applicant"     => "required|string",
            "motive"        => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function getRoomsAvailableForReserve(mixed $dateAvailable, bool $onlyAvailable)
    {
        $dayStart = Carbon::parse($dateAvailable . ' 00:00:00');
        $dayEnd = Carbon::parse($dateAvailable . ' 23:59:59');

        $availableRoomIds = Room::select()
            ->whereDoesntHave('roomReserves', function ($query) {
                $query->where('is_confirmed', true);
            })
            ->orderBy('name', 'asc')
            ->pluck('id')
            ->toArray();

        if ($onlyAvailable) {
            return $availableRoomIds;
        }

        $reserves = RoomReserve::query()
            ->whereNotIn('room_id', $availableRoomIds)
            ->where('is_confirmed', true)
            ->whereDate('date', $dateAvailable)
            ->orderBy('room_id')
            ->orderBy('hour_start')
            ->get()
            ->groupBy('room_id');

        foreach ($reserves as $roomId => $items) {
            $cursor = $dayStart->copy();

            foreach ($items as $reserve) {
                $start = Carbon::parse($dateAvailable . ' ' . $reserve->hour_start);
                $end = Carbon::parse($dateAvailable . ' ' . $reserve->hour_end);

                // Existe un hueco antes de esta reserva
                if ($cursor->lt($start)) {
                    $availableRoomIds[] = $roomId;
                    continue 2;
                }

                // Avanza el cursor hasta el final de la reserva
                if ($end->gt($cursor)) {
                    $cursor = $end->copy();
                }
            }

            // Existe un hueco después de la última reserva
            if ($cursor->lt($dayEnd)) {
                $availableRoomIds[] = $roomId;
            }
        }

        return $availableRoomIds;
    }
}
