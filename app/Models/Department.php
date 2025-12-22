<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_id',
        'description',
        'status',
        'created_by',
        'manager_id',
        'validator2_id',
        'director_id'
    ];

    /**
     * Get the manager of the department.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the branch that owns the department.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the validator 2 of the department.
     */
    public function validator2()
    {
        return $this->belongsTo(User::class, 'validator2_id');
    }

    /**
     * Get the facility director of the department.
     */
    public function director()
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    /**
     * Get the user who created the department.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the employees assigned to this department.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function desginations()
    {
        return $this->hasMany(Designation::class, 'department_id', 'id');
    }
}