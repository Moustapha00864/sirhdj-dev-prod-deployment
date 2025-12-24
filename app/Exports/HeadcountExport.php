<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HeadcountExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $companyUserIds = getCompanyAndUsersId();
        $query = Employee::with(['user', 'department', 'branch', 'designation']);

        if (isset($this->filters['department_id']) && $this->filters['department_id'] !== 'all') {
            $query->where('department_id', $this->filters['department_id']);
        }

        if (isset($this->filters['branch_id'])) {
            $query->where('branch_id', $this->filters['branch_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID Emp',
            'Nom Complet',
            'Genre',
            'Département',
            'Poste',
            'Branche',
            'Type de Contrat',
            'Date d\'embauche',
            'Statut'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_id,
            $employee->user->name ?? '',
            $employee->gender,
            $employee->department->name ?? '',
            $employee->designation->name ?? '',
            $employee->branch->name ?? '',
            $employee->contractType->name ?? '',
            $employee->date_of_joining,
            $employee->user->is_active ? 'Actif' : 'Inactif'
        ];
    }
}
