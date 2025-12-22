<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Holiday;

class LeaveCalculationService
{
    /**
     * Calculate total working days between two dates, excluding weekends and holidays.
     *
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @param array $branchIds Optional branch IDs to filter holidays
     * @return float
     */
    public function calculateWorkingDays($startDate, $endDate, $branchIds = [])
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        $period = CarbonPeriod::create($start, $end);

        // Fetch holidays in the range
        $holidays = Holiday::where(function ($query) use ($start, $end) {
            $query->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
        })->get();

        $totalDays = 0;

        foreach ($period as $date) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            // Check if it's a holiday
            $isHoliday = false;
            foreach ($holidays as $holiday) {
                if ($date->between($holiday->start_date, $holiday->end_date)) {
                    // Check branch restriction if applicable
                    if ($holiday->branches->isNotEmpty() && !empty($branchIds)) {
                        $branchIntersection = $holiday->branches->pluck('id')->intersect($branchIds);
                        if ($branchIntersection->isEmpty()) {
                            continue; // This holiday doesn't apply to these branches
                        }
                    }

                    if ($holiday->is_half_day) {
                        $totalDays += 0.5;
                        $isHoliday = true; // Still marked as holiday for this date logic
                        break;
                    } else {
                        $isHoliday = true;
                        break;
                    }
                }
            }

            if (!$isHoliday) {
                $totalDays += 1;
            }
        }

        return $totalDays;
    }
}
