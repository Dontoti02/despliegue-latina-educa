<?php

namespace Modules\Tenant\Packages\Room\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Room\Helpers\RoomHelper;
use Modules\Tenant\Models\Room;
use Modules\Tenant\Models\RoomReserve;
use Modules\Tenant\Models\RoomType;

class RoomRepository
{
    public static function params()
    {
        $roomTypes = RoomType::select()
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'room_types' => $roomTypes,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        RoomHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $capacity = $request->input('capacity');
        $roomTypeId = $request->input('room_type_id');
        $dateAvailable = $request->input('date_available');
        $onlyAvailable = $request->input('only_available');

        $rooms = Room::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->when($capacity, function ($query) use ($capacity) {
                $query->where('capacity', '>=', $capacity);
            })
            ->when($roomTypeId, function ($query) use ($roomTypeId) {
                $query->where('room_type_id', $roomTypeId);
            })
            ->when($dateAvailable || $onlyAvailable, function ($query) use ($dateAvailable, $onlyAvailable) {
                $query->whereIn('id', RoomHelper::getRoomsAvailableForReserve($dateAvailable, $onlyAvailable));
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $roomsMap = [];
        foreach ($rooms->items() as $room) {
            $reserves = $room
                ->roomReserves()
                ->where('is_confirmed', true)
                ->count();

            $label = 'LIBRE';

            if ($reserves === 1) {
                $label = $reserves . ' RESERVA';
            }

            if ($reserves > 1) {
                $label = $reserves . ' RESERVAS';
            }

            $roomsMap[] = [
                'id' => $room->id,
                'name' => $room->name,
                'location' => $room->location,
                'room_type_id' => $room->room_type_id,
                'room_type_name' => $room->roomType ? $room->roomType->name : null,
                'capacity' => $room->capacity,
                'is_active' => $room->is_active,
                'label' => $label,
                'image' => $room->image,
            ];
        }

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $rooms->total(),
            'items' => $roomsMap,
        ];

        return $result;
    }

    public static function uploadImage(Request $request)
    {
        RoomHelper::validateUploadImageRequest($request);

        $file = $request->file('file');

        $path = $file->store('public/room');
        $path = str_replace('public/', '', $path);

        return $path;
    }

    public static function create(Request $request)
    {
        RoomHelper::validateRequest($request);

        $name = $request->input('name');
        $location = $request->input('location');
        $roomTypeId = $request->input('room_type_id');
        $capacity = $request->input('capacity');
        $isActive = $request->input('is_active');
        $image = $request->input('image');

        $existsRoom = Room::select()
            ->where('name', $name)
            ->where('location', $location)
            ->exists();

        if ($existsRoom) {
            throw new Exception('Ya existe un ambiente con el mismo nombre y ubicación.');
        }

        Room::create([
            'name' => $name,
            'location' => $location,
            'room_type_id' => $roomTypeId,
            'capacity' => $capacity,
            'is_active' => $isActive,
            'image' => $image,
        ]);

        return 'Ambiente creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        RoomHelper::validateRequest($request);

        $name = $request->input('name');
        $location = $request->input('location');
        $roomTypeId = $request->input('room_type_id');
        $capacity = $request->input('capacity');
        $isActive = $request->input('is_active');
        $image = $request->input('image');

        $room = Room::findOrFail($id);

        $existsRoom = Room::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->where('location', $location)
            ->exists();

        if ($existsRoom) {
            throw new Exception('Ya existe un ambiente con el mismo nombre y ubicación.');
        }

        $room->update([
            'name' => $name,
            'location' => $location,
            'room_type_id' => $roomTypeId,
            'capacity' => $capacity,
            'is_active' => $isActive,
            'image' => $image,
        ]);

        return 'Ambiente actualizado correctamente.';
    }

    public static function delete(int $id)
    {
        $room = Room::findOrFail($id);

        if ($room->roomReserves()->exists()) {
            throw new Exception('No se puede eliminar el ambiente porque tiene reservas asociadas.');
        }

        $room->delete();

        return 'Ambiente eliminado correctamente.';
    }

    public static function listReserves(Request $request)
    {
        RoomHelper::validateListReservesRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $roomId = $request->input('room_id');
        $date = $request->input('date');
        $isPending = $request->input('is_pending');

        $reserves = RoomReserve::select()
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->when($isPending, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereDate('date', '>', Carbon::now()->toDateString())
                        ->orWhere(function ($dateQuery) {
                            $dateQuery->whereDate('date', Carbon::now()->toDateString())
                                ->where('hour_start', '>', Carbon::now()->toTimeString());
                        });
                });
            })
            ->when($isPending === false, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereDate('date', '<', Carbon::now()->toDateString())
                        ->orWhere(function ($dateQuery) {
                            $dateQuery->whereDate('date', Carbon::now()->toDateString())
                                ->where('hour_start', '<=', Carbon::now()->toTimeString());
                        });
                });
            })
            ->orderBy('hour_start', 'desc')
            ->paginate($size, ['*'], 'page', $page);

        $reservesMap = [];
        foreach ($reserves->items() as $reserve) {
            $reservesMap[] = [
                'id' => $reserve->id,
                'room_id' => $reserve->room_id,
                'date' => Carbon::parse($reserve->date)->format('Y-m-d'),
                'hour_start' => Carbon::parse($reserve->hour_start)->format('h:i A'),
                'hour_end' => Carbon::parse($reserve->hour_end)->format('h:i A'),
                'applicant' => $reserve->applicant,
                'motive' => $reserve->motive,
                'is_confirmed' => $reserve->is_confirmed,
                'status' => Carbon::parse($reserve->date . ' ' . $reserve->hour_start)->gt(Carbon::now()) ? 'Pendiente' : 'Finalizado',
            ];
        }

        $dates = RoomReserve::select()
            ->where('room_id', $roomId)
            ->whereYear('date', Carbon::parse($date)->year)
            ->whereMonth('date', Carbon::parse($date)->month)
            ->pluck('date')
            ->unique()
            ->values()
            ->toArray();

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $reserves->total(),
            'items' => $reservesMap,
            'dates' => $dates,
        ];

        return $result;
    }

    public static function addReserve(Request $request)
    {
        $hourStartMinimum = '06:00';
        $hourEndMaximum = '18:00';

        RoomHelper::validateAddReserveRequest($request);

        $roomId = $request->input('room_id');
        $dates = $request->input('dates');
        $hourStart = $request->input('hour_start');
        $hourEnd = $request->input('hour_end');
        $applicant = $request->input('applicant');
        $motive = $request->input('motive');

        $now = Carbon::now();
        $dateStart = Carbon::createFromFormat('H:i', $request->hour_start);
        $dateEnd = Carbon::createFromFormat('H:i', $request->hour_end);

        if ($dateEnd->lte($dateStart)) {
            throw new Exception('La hora de fin debe ser mayor que la hora de inicio.');
        }

        if ($dateStart->diffInMinutes($dateEnd) < 30) {
            throw new Exception('El tiempo mínimo para una reserva es de 30 minutos.');
        }

        if ($dateStart->format('H:i') < $hourStartMinimum || $dateEnd->format('H:i') > $hourEndMaximum) {
            $a = Carbon::parse($hourStartMinimum)->format('h:i A');
            $b = Carbon::parse($hourEndMaximum)->format('h:i A');

            throw new Exception("El horario de reserva debe estar entre $a y $b.");
        }

        $reserveRecords = [];
        foreach ($dates as $date) {
            if ($date < $now->format('Y-m-d')) {
                throw new Exception('La fecha de reserva debe ser mayor o igual a la fecha actual.');
            }

            if ($date === $now->format('Y-m-d') && $dateStart->startOfHour()->lt($now->startOfHour())) {
                throw new Exception('La hora de inicio debe ser mayor o igual a la hora actual.');
            }

            $existsReserve = RoomReserve::select()
                ->where('is_confirmed', true)
                ->where('room_id', $roomId)
                ->whereDate('date', $date)
                ->where('hour_start', '<', $hourEnd)
                ->where('hour_end', '>', $hourStart)
                ->first();

            if ($existsReserve) {
                $a = $dateStart->format('h:i A');
                $b = $dateEnd->format('h:i A');

                throw new Exception("El ambiente ya está reservado entre $a y $b.");
            }

            $reserveRecords[] = [
                'room_id' => $roomId,
                'date' => $date,
                'hour_start' => $hourStart,
                'hour_end' => $hourEnd,
                'applicant' => $applicant,
                'motive' => $motive,
                'is_confirmed' => true,
            ];
        }

        RoomReserve::insert($reserveRecords);

        return 'Reserva de ambiente creada correctamente.';
    }

    public static function deleteReserve(int $roomReserveId)
    {
        $roomReserve = RoomReserve::findOrFail($roomReserveId);

        $roomReserve->delete();

        return 'Reserva de ambiente eliminada correctamente.';
    }

    public static function dashboard(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $averageOccupancy = self::getAverageOccupancy($month, $year);
        $mostRequestedRooms = self::getMostRequestedRooms($month, $year);
        $mostUsedRooms = self::mostUsedRooms($month, $year);
        $recentReservations = self::getRecentReservations($month, $year);
        $weeklyUsageTrends = self::getWeeklyUsageTrends($month, $year);
        
        $result = [
            'average_occupancy' => $averageOccupancy,
            'most_requested_rooms' => $mostRequestedRooms,
            'weekly_usage_trends' => $weeklyUsageTrends,
            'most_used_rooms' => $mostUsedRooms,
            'recent_reservations' => $recentReservations,
        ];

        return $result;
    }

    private static function getWeeklyUsageTrends($month, $year)
    {
        $targetDate = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $targetDate->daysInMonth;

        $dayOccurrences = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayName = $targetDate->copy()->day($i)->format('l');
            if (!isset($dayOccurrences[$dayName])) {
                $dayOccurrences[$dayName] = 0;
            }
            $dayOccurrences[$dayName]++;
        }

        $monthlyTrends = RoomReserve::selectRaw('DAYNAME(date) as day, SUM(TIMESTAMPDIFF(MINUTE, hour_start, hour_end)) as total_minutes')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('is_confirmed', true)
            ->groupBy('day')
            ->get();

        $categories = ["Lun", "Mar", "Mie", "Jue", "Vie", "Sab", "Dom"];
        $seriesData = [0, 0, 0, 0, 0, 0, 0];
        
        $dayMap = [
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
            'Sunday' => 6,
        ];

        $totalRooms = Room::where('is_active', true)->count();
        $totalRooms = $totalRooms > 0 ? $totalRooms : 1;
        $dailyAvailableMinutesPerRoom = 12 * 60; 
        
        $totalMinutesReservedMonth = 0;
        $totalPossibleMinutesMonth = $totalRooms * $dailyAvailableMinutesPerRoom * $daysInMonth;

        foreach ($monthlyTrends as $trend) {
            if (isset($dayMap[$trend->day])) {
                $index = $dayMap[$trend->day];
                $totalMinutes = (int) $trend->total_minutes;
                $totalMinutesReservedMonth += $totalMinutes;

                $occurrences = $dayOccurrences[$trend->day] ?? 1;
                $minutesAvailableForThisWeekday = $totalRooms * $dailyAvailableMinutesPerRoom * $occurrences;

                $percentage = ($totalMinutes / $minutesAvailableForThisWeekday) * 100;
                $seriesData[$index] = round($percentage > 100 ? 100 : $percentage);
            }
        }

        // 2. Calcular Horas Pico en el mes
        $peakHourQuery = RoomReserve::selectRaw('HOUR(hour_start) as peak_hour, COUNT(*) as count')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('is_confirmed', true)
            ->groupBy('peak_hour')
            ->orderByDesc('count')
            ->first();

        $peakHours = "10:00 AM - 01:00 PM";
        if ($peakHourQuery && $peakHourQuery->peak_hour) {
            $startPeak = Carbon::createFromTime($peakHourQuery->peak_hour, 0);
            $endPeak = $startPeak->copy()->addHours(3);
            $peakHours = $startPeak->format('h:i A') . ' - ' . $endPeak->format('h:i A');
        }

        $totalVacancyMinutes = $totalPossibleMinutesMonth - $totalMinutesReservedMonth;
        
        $vacancyHoursPerDay = ($totalVacancyMinutes / 60) / $daysInMonth;
        $vacancyHoursPerRoomPerDay = $vacancyHoursPerDay / $totalRooms;
        $vacancyTime = round($vacancyHoursPerRoomPerDay, 1) . " horas/día";

        return [
            'categories' => $categories,
            'seriesData' => $seriesData,
            'peakHours' => $peakHours,
            'vacancyTime' => $vacancyTime,
        ];
    }

    private static function getRecentReservations($month, $year)
    {
        $recentReservations = RoomReserve::select()
            ->with('room')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $result = [];
        foreach ($recentReservations as $reservation) {
            $result[] = [
                'id' => $reservation->id,
                'room_id' => $reservation->room_id,
                'room_name' => $reservation->room ? $reservation->room->name : null,
                'location' => $reservation->room ? $reservation->room->location : null,
                'date' => Carbon::parse($reservation->date)->format('Y-m-d'),
                'hour_start' => Carbon::parse($reservation->hour_start)->format('h:i A'),
                'hour_end' => Carbon::parse($reservation->hour_end)->format('h:i A'),
                'applicant' => $reservation->applicant,
                'motive' => $reservation->motive,
                'is_confirmed' => $reservation->is_confirmed,
                'status' => Carbon::parse($reservation->date . ' ' . $reservation->hour_start)->gt(Carbon::now()) ? 'Pendiente' : 'Finalizado',
            ];
        }

        return $result;
    }

    private static function mostUsedRooms($month, $year)
    {
        $rooms = Room::select()
            ->withCount([
                'roomReserves' => function ($query) use ($month, $year) {
                    $query->where('is_confirmed', true)
                          ->whereMonth('date', $month)
                          ->whereYear('date', $year);
                }
            ])
            ->orderByDesc('room_reserves_count')
            ->take(5)
            ->get();

        $result = [];
        foreach ($rooms as $room) {
            $result[] = [
                'id' => $room->id,
                'name' => $room->name,
                'total_reserves' => $room->room_reserves_count,
                'capacity' => $room->capacity ?? 0,
                'utilization_percentage' => $room->capacity ? round(($room->room_reserves_count / $room->capacity) * 100, 2) : 0,
            ];
        }

        return $result;
    }

    private static function getAverageOccupancy($month, $year)
    {
        $hourStartMinimum = Carbon::parse('06:00');
        $hourEndMaximum = Carbon::parse('18:00');

        $diffInMinutes = $hourStartMinimum->diffInMinutes($hourEndMaximum);

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $previousDate = $currentDate->copy()->subMonth();

        $totalMinutes = $diffInMinutes * $currentDate->daysInMonth;
        $previousTotalMinutes = $diffInMinutes * $previousDate->daysInMonth;

        $roomIds = Room::select('id')->pluck('id')->toArray();

        function avgTotal(array $roomIds, Carbon $date, int $totalMinutesMax)
        {
            $totalMinutesByRooms = [];
            foreach ($roomIds as $roomId) {
                $totalMinutesByRoom = RoomReserve::query()
                    ->where('room_id', $roomId)
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->where('is_confirmed', true)
                    ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, hour_start, hour_end)) AS total')
                    ->value('total');

                $totalMinutesByRooms[] = (int) $totalMinutesByRoom ?? 0;
            }

            $avg = count($totalMinutesByRooms) > 0 ? round(array_sum($totalMinutesByRooms) / count($totalMinutesByRooms), 2) : 0;
            $avgTotal = $totalMinutesMax > 0 ? round(($avg * 100) / $totalMinutesMax, 2) : 0;

            return $avgTotal;
        }

        $percentage = avgTotal($roomIds, $currentDate, $totalMinutes);
        $previousAverageOccupancy = avgTotal($roomIds, $previousDate, $previousTotalMinutes);
        $percentageDiff = $percentage - $previousAverageOccupancy;

        $result = [
            'percentage' => $percentage,
            'percentage_diff' => $percentageDiff,
        ];

        return $result;
    }

    private static function getMostRequestedRooms($month, $year)
    {
        $room = Room::query()
            ->withCount([
                'roomReserves' => function ($query) use ($month, $year) {
                    $query->where('is_confirmed', true)
                          ->whereMonth('date', $month)
                          ->whereYear('date', $year);
                }
            ])
            ->orderByDesc('room_reserves_count')
            ->first();

        $result = $room ? [
            'id' => $room->id,
            'name' => $room->name,
            'total_reserves' => $room->room_reserves_count,
        ] : null;

        return $result;
    }
}