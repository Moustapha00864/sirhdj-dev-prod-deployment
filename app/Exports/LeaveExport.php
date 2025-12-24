<?php

namespace App\Exports;

use App\Models\LeaveApplication;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class LeaveExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $companyUserIds = getCompanyAndUsersId();
        $year = $this->filters['year'] ?? now()->year;

        $query = LeaveApplication::where('status', 'approved')
            ->whereYear('start_date', $year)
            ->with(['employee.employee.department', 'leaveType']);

        if (isset($this->filters['department_id']) && $this->filters['department_id'] !== 'all') {
            $query->whereHas('employee.employee', function ($q) {
                $q->where('department_id', $this->filters['department_id']);
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Employé',
            'Département',
            'Type de Congé',
            'Date Début',
            'Date Fin',
            'Jours',
            'Raison',
            'Status'
        ];
    }

    public function map($leave): array
    {
        return [
            $leave->employee->name ?? '',
            $leave->employee->employee->department->name ?? '',
            $leave->leaveType->title ?? '',
            $leave->start_date,
            $leave->end_date,
            $leave->total_days,
            $leave->reason,
            $leave->status
        ];
    }
}
