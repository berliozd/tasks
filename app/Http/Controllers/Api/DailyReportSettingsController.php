<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyReportSettingsController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'daily_report_enabled' => ['required', 'boolean'],
            'daily_report_hour' => ['required', 'integer', 'min:0', 'max:23'],
            'daily_report_minute' => ['required', 'integer', Rule::in([0, 15, 30, 45])],
        ]);

        $user = $request->user();
        $user->update($data);

        return $user->only(['daily_report_enabled', 'daily_report_hour', 'daily_report_minute']);
    }
}
