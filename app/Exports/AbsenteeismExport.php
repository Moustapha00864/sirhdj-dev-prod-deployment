<?php

namespace App\Exports;

use App\Models\AttendanceRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AbsenteeismExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $companyUserIds = getCompanyAndUsersId();
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $this->filters['end_date'] ?? now()->toDateString();

        $query = AttendanceRecord::whereBetween('date', [$startDate, $endDate])
            ->where('status', 'absent')
            ->with(['employee.employee.department']);

        if (isset($this->filters['department_id'])) {
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
            'Date d\'absence',
            'Notes',
            'Justifié'
        ];
    }

    public function map($record): array
    {
        return [
            $record->employee->name ?? '',
            $record->employee->employee->department->name ?? '',
            $record->date->format('d/m/Y'),
            $record->notes,
            'Non' // Logic for justification can be added later
        ];
    }
}
