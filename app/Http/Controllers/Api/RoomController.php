<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\MasterData\Bed;
use App\Models\MasterData\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Room & Bed Management API Controller.
 * 
 * Handles room and bed information, occupancy status, and availability.
 */
class RoomController extends BaseController
{
    /**
     * Display a listing of rooms.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Room::query()
            ->with(['floor', 'roomType', 'beds'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->floor_id, fn($q, $f) => $q->where('floor_id', $f))
            ->when($request->room_type_id, fn($q, $t) => $q->where('room_type_id', $t))
            ->when($request->is_active !== null, fn($q, $a) => $q->where('is_active', $a));

        $rooms = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginateResponse($rooms);
    }

    /**
     * Display the specified room.
     *
     * @param Room $room
     * @return JsonResponse
     */
    public function show(Room $room): JsonResponse
    {
        return $this->successResponse(
            $room->load(['floor', 'roomType', 'beds.patient'])
        );
    }

    /**
     * Get beds for a specific room.
     *
     * @param Request $request
     * @param Room $room
     * @return JsonResponse
     */
    public function beds(Request $request, Room $room): JsonResponse
    {
        $beds = $room->beds()
            ->with(['patient' => function ($q) {
                $q->select('id', 'name', 'medical_record_number');
            }])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->is_available !== null, fn($q, $a) => $q->where('is_available', $a))
            ->get();

        return $this->successResponse([
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'code' => $room->code,
            ],
            'beds' => $beds,
            'total_beds' => $beds->count(),
            'available_beds' => $beds->where('is_available', true)->count(),
            'occupied_beds' => $beds->where('is_available', false)->count(),
        ]);
    }

    /**
     * Get room occupancy statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function occupancy(Request $request): JsonResponse
    {
        $request->validate([
            'floor_id' => ['nullable', 'exists:floors,id'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);

        $query = Room::query()
            ->when($request->floor_id, fn($q, $f) => $q->where('floor_id', $f))
            ->when($request->room_type_id, fn($q, $t) => $q->where('room_type_id', $t));

        $rooms = $query->with(['beds', 'floor', 'roomType'])->get();

        $totalBeds = $rooms->sum(fn($r) => $r->beds->count());
        $occupiedBeds = $rooms->sum(fn($r) => $r->beds->where('is_available', false)->count());
        $availableBeds = $totalBeds - $occupiedBeds;

        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 2) : 0;

        $byRoom = $rooms->map(function ($room) {
            $total = $room->beds->count();
            $occupied = $room->beds->where('is_available', false)->count();

            return [
                'id' => $room->id,
                'name' => $room->name,
                'code' => $room->code,
                'floor' => $room->floor?->name,
                'room_type' => $room->roomType?->name,
                'total_beds' => $total,
                'occupied' => $occupied,
                'available' => $total - $occupied,
                'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 2) : 0,
            ];
        });

        return $this->successResponse([
            'summary' => [
                'total_rooms' => $rooms->count(),
                'total_beds' => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'available_beds' => $availableBeds,
                'occupancy_rate' => $occupancyRate,
            ],
            'by_room' => $byRoom,
        ]);
    }

    /**
     * Get available beds across all rooms.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableBeds(Request $request): JsonResponse
    {
        $request->validate([
            'floor_id' => ['nullable', 'exists:floors,id'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
            'room_class' => ['nullable', 'in:vvip,vip,1,2,3'],
        ]);

        $query = Bed::query()
            ->with([
                'room' => fn($q) => $q->select('id', 'name', 'code', 'room_type_id', 'floor_id'),
                'room.floor:id,name',
                'room.roomType:id,name,class',
            ])
            ->where('is_available', true)
            ->whereHas('room', fn($q) => $q->where('is_active', true))
            ->when($request->floor_id, fn($q, $f) => $q->whereHas('room', fn($sq) => $sq->where('floor_id', $f)))
            ->when($request->room_type_id, fn($q, $t) => $q->whereHas('room', fn($sq) => $sq->where('room_type_id', $t)))
            ->when($request->room_class, fn($q, $c) => $q->whereHas('room.roomType', fn($sq) => $sq->where('class', $c)));

        $beds = $query->orderBy('room_id')->orderBy('bed_number')->paginate($request->per_page ?? 30);

        return $this->paginateResponse($beds);
    }
}
