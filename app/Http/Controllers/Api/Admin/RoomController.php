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
}
