<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'leave_policy_id',
        'year',
        'allocated_days',
        'used_days',
        'remaining_days',
        'carried_forward',
        'manual_adjustment',
        'adjustment_reason',
        'initial_leave_balance',
        'carry_over_years',
        'created_by'
    ];

    protected $casts = [
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'carried_forward' => 'decimal:2',
        'manual_adjustment' => 'decimal:2',
        'initial_leave_balance' => 'decimal:2',
        'carry_over_years' => 'integer',
    ];

    /**
     * Get the employee.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * Get the leave type.
     */
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the leave policy.
     */
    public function leavePolicy()
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    /**
     * Get the user who created the balance.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the movements for this balance.
     */
    public function movements()
    {
        return $this->hasMany(LeaveMovement::class);
    }

    /**
     * Calculate remaining days.
     */
    public function calculateRemainingDays()
    {
        $this->remaining_days = ($this->allocated_days + $this->carried_forward + $this->manual_adjustment) - $this->used_days;
        return $this->remaining_days;
    }
}