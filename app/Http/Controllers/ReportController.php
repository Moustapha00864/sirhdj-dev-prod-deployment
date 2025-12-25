<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\AttendanceRecord;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\HeadcountExport;
use App\Exports\LeaveExport;
use App\Exports\AbsenteeismExport;
use App\Exports\EmploiExport;

class ReportController extends Controller
{
    public function index()
    {
        $companyUserIds = getCompanyAndUsersId();
        $departments = Department::whereIn('created_by', $companyUserIds)->get();
        return Inertia::render('hr/reports/index', [
            'departments' => $departments
        ]);
    }

    public function getHeadcountReport(Request $request)
    {
        $companyUserIds = getCompanyAndUsersId();

        $query = Employee::whereIn('created_by', $companyUserIds)
            ->with(['user', 'department', 'branch', 'designation', 'contractType']);

        if ($request->department_id && $request->department_id !== 'all') {
            $query->where('department_id', $request->department_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $employees = $query->get();

        // Stats for charts
        $byDepartment = $employees->groupBy('department.name')->map->count();
        $byContractType = $employees->groupBy('contractType.name')->map->count();
        $byGender = $employees->groupBy('gender')->map->count();

        return response()->json([
            'data' => $employees,
            'charts' => [
                'byDepartment' => $byDepartment,
                'byContractType' => $byContractType,
                'byGender' => $byGender,
            ]
        ]);
    }

    public function getLeaveReport(Request $request)
    {
        $companyUserIds = getCompanyAndUsersId();
        $year = $request->year ?? now()->year;

        $query = LeaveApplication::whereIn('created_by', $companyUserIds)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->with(['employee.employee.department', 'leaveType']);

        if ($request->department_id) {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $leaves = $query->get();

        $byMonth = $leaves->groupBy(function ($date) {
            return Carbon::parse($date->start_date)->format('M');
        })->map(function ($month) {
            return $month->sum('total_days');
        });

        $byType = $leaves->groupBy('leaveType.title')->map->count();

        return response()->json([
            'data' => $leaves,
            'charts' => [
                'byMonth' => $byMonth,
                'byType' => $byType,
            ]
        ]);
    }

    public function getAbsenteeismReport(Request $request)
    {
        $companyUserIds = getCompanyAndUsersId();
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $query = AttendanceRecord::whereIn('created_by', $companyUserIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'absent')
            ->with(['employee.employee.department']);

        if ($request->department_id) {
            $query->whereHas('employee.employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $absents = $query->get();

        $byDate = $absents->groupBy('date')->map->count();
        $byDepartment = $absents->groupBy('employee.employee.department.name')->map->count();

        return response()->json([
            'data' => $absents,
            'charts' => [
                'byDate' => $byDate,
                'byDepartment' => $byDepartment,
            ]
        ]);
    }

    public function getEmploiReport(Request $request)
    {
        $companyUserIds = getCompanyAndUsersId();

        $query = Employee::whereIn('created_by', $companyUserIds)
            ->with(['user', 'department', 'branch', 'designation', 'contractType']);

        if ($request->department_id && $request->department_id !== 'all') {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->get();

        // Stats for charts (reusing headcount logic as requested by UI)
        $byDepartment = $employees->groupBy('department.name')->map->count();
        $byContractType = $employees->groupBy('contractType.name')->map->count();
        $byGender = $employees->groupBy('gender')->map->count();

        return response()->json([
            'data' => $employees,
            'charts' => [
                'byDepartment' => $byDepartment,
                'byContractType' => $byContractType,
                'byGender' => $byGender,
            ]
        ]);
    }

    public function export(Request $request)
    {
        $type = $request->input('type'); // headcount, leave, absenteeism, emploi
        $format = $request->input('format', 'xlsx'); // xlsx, csv, pdf

        if ($type === 'emploi') {
            if (!$request->department_id || $request->department_id === 'all') {
                return back()->with('error', 'Veuillez sélectionner un département spécifique pour le rapport d\'emploi.');
            }
            return Excel::download(new EmploiExport($request->all()), "rapport_emploi_" . now()->format('Y-m-d') . ".$format");
        }

        if ($type === 'headcount') {
            return Excel::download(new HeadcountExport($request->all()), "rapport_effectifs_" . now()->format('Y-m-d') . ".$format");
        }

        if ($type === 'leave') {
            return Excel::download(new LeaveExport($request->all()), "rapport_conges_" . now()->format('Y-m-d') . ".$format");
        }

        if ($type === 'absenteeism') {
            return Excel::download(new AbsenteeismExport($request->all()), "rapport_absenteisme_" . now()->format('Y-m-d') . ".$format");
        }

        // Add others...
        return back()->with('error', 'Export type non supporté pour le moment');
    }
}
