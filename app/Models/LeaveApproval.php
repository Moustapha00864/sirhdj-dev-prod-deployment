<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    protected $fillable = [
        'leave_application_id',
        'stage',
        'approver_id',
        'status',
        'comments',
        'actioned_at'
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
