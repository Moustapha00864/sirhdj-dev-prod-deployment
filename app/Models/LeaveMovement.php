<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveMovement extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'leave_balance_id',
        'type',
        'days',
        'reason',
        'created_by'
    ];

    /**
     * Get the leave balance associate with this movement.
     */
    public function leaveBalance()
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    /**
     * Get the user who created the movement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
