<?php

namespace App\Http\Controllers;

use App\Models\LeaveSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LeaveSettingController extends Controller
{
    /**
     * Display the global leave settings.
     */
    public function index()
    {
        $settings = [
            'holiday_exclusion' => LeaveSetting::get('holiday_exclusion', 'none'),
            'carry_over_limit' => LeaveSetting::get('carry_over_limit', 0),
            'default_initial_balance' => LeaveSetting::get('default_initial_balance', 21),
            'weekend_exclusion' => LeaveSetting::get('weekend_exclusion', 'both'),
        ];

        return Inertia::render('hr/leave-settings/index', [
            'settings' => $settings
        ]);
    }

    /**
     * Update the global leave settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'holiday_exclusion' => 'required|in:none,national,branch',
            'carry_over_limit' => 'required|integer|min:0',
            'default_initial_balance' => 'required|numeric|min:0',
            'weekend_exclusion' => 'required|in:none,saturday,sunday,both',
        ]);

        foreach ($validated as $key => $value) {
            LeaveSetting::set($key, $value, Auth::id());
        }

        return redirect()->back()->with('success', __('Leave settings updated successfully.'));
    }
}
