<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function changeMatriculation(Request $request): JsonResponse
    {
        $data = $request->validate(['open' => ['required', 'boolean']]);

        Setting::put('change_matriculation_open', $data['open'] ? '1' : '0');

        AuditLog::record(
            'Change Matriculation Window ' . ($data['open'] ? 'Opened' : 'Closed'),
            'Admin set the change of matriculation window to ' . ($data['open'] ? 'OPEN' : 'CLOSED') . '.',
            'Setting'
        );

        return response()->json(['change_matriculation_open' => $data['open']]);
    }
}
