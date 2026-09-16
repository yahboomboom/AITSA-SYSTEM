<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Section;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FacultyController extends Controller
{
    public function index(EnrollmentService $enrollments): JsonResponse
    {
        $year = $enrollments->currentTerm()['school_year'];

        return response()->json([
            'faculty' => User::where('role', 'faculty')->orderBy('name')
                ->withCount(['taughtSections as sections_count' => fn ($q) => $q->where('school_year', $year)])
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id, 'name' => $u->name, 'login_id' => $u->login_id,
                    'sections_count' => $u->sections_count,
                ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'login_id' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,login_id'],
        ]);

        $faculty = User::create([
            'name' => $data['name'],
            'login_id' => $data['login_id'],
            'email' => $data['login_id'].'@faculty.aitsa.test',
            'password' => Hash::make('password123'),
            'role' => 'faculty',
        ]);

        AuditLog::record('Faculty Created', "Faculty account created for {$faculty->name} ({$faculty->login_id}).", 'User', $faculty->id);

        return response()->json(['faculty' => [
            'id' => $faculty->id, 'name' => $faculty->name, 'login_id' => $faculty->login_id, 'sections_count' => 0,
        ]], 201);
    }

    public function schedule(User $user, EnrollmentService $enrollments): JsonResponse
    {
        if ($user->role !== 'faculty') {
            return response()->json(['message' => 'That user is not a faculty member.'], 422);
        }

        $year = $enrollments->currentTerm()['school_year'];

        return response()->json([
            'schedule' => $user->taughtSections()->where('school_year', $year)
                ->with(['subject', 'roomEntity'])->orderBy('start_time')->get()
                ->map(fn (Section $s) => [
                    'id' => $s->id,
                    'subject_code' => $s->subject->code,
                    'subject_title' => $s->subject->title,
                    'block_label' => $s->block_label,
                    'days' => $s->days,
                    'start_time' => $s->start_time,
                    'end_time' => $s->end_time,
                    'room_label' => $s->roomLabel(),
                    'online' => $s->roomEntity !== null && ! $s->roomEntity->isPhysical(),
                ])->values(),
        ]);
    }
}
