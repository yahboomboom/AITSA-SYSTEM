<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['rooms' => Room::orderBy('name')->get(['id', 'name', 'type'])]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:rooms,name'],
            'type' => ['required', 'in:physical,virtual'],
        ]);

        $room = Room::create($data);

        AuditLog::record('Room Created', "Room {$room->name} ({$room->type}) added.", 'Room', $room->id);

        return response()->json(['room' => $room], 201);
    }

    public function destroy(Room $room): JsonResponse
    {
        if ($room->sections()->exists()) {
            return response()->json(['message' => 'This room is assigned to a section and cannot be deleted.'], 409);
        }

        AuditLog::record('Room Deleted', "Room {$room->name} deleted.", 'Room', $room->id);
        $room->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
