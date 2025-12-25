<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmploiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Employee::with(['user', 'department', 'branch', 'designation', 'contractType']);

        // Conditional export: must have department_id or it stays constrained by company
        if (isset($this->filters['department_id']) && $this->filters['department_id'] !== 'all') {
            $query->where('department_id', $this->filters['department_id']);
        }

        $companyUserIds = getCompanyAndUsersId();
        $query->whereIn('created_by', $companyUserIds);

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'Nom Complet',
            'Genre',
            'Date de Naissance',
            'Situation Matrimoniale',
            'Département / Service',
            'Poste / Spécialité',
            'Type de Personnel',
            'Type de Contrat',
            'Date d\'Engagement',
            'Statut'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->employee_id,
            $employee->user->name ?? '',
            $employee->gender === 'male' ? 'Masculin' : ($employee->gender === 'female' ? 'Féminin' : 'Autre'),
            $employee->date_of_birth,
            $employee->matrimonial_status ?? 'Non défini',
            $employee->department->name ?? '',
            $employee->designation->name ?? '',
            $employee->personnel_type ?? 'Non défini',
            $employee->contractType->name ?? '',
            $employee->date_of_joining,
            $employee->user->is_active ? 'Actif' : 'Inactif'
        ];
    }
}
