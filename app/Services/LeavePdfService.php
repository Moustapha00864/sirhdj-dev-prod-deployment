<?php

namespace App\Services;

use App\Models\LeaveApplication;
use Barryvdh\DomPDF\Facade\Pdf;

class LeavePdfService
{
    /**
     * Generate a PDF summary for a leave application.
     *
     * @param LeaveApplication $leave
     * @return \Illuminate\Http\Response
     */
    public function generateSummary(LeaveApplication $leave)
    {
        $leave->load(['employee', 'employee.employee.department', 'leaveType', 'approvals.approver']);

        $pdf = Pdf::loadView('pdfs.leave_summary', compact('leave'));

        return $pdf->download('leave_summary_' . $leave->id . '.pdf');
    }

    /**
     * Save PDF summary to storage and return the path.
     *
     * @param LeaveApplication $leave
     * @return string
     */
    public function saveSummaryToStorage(LeaveApplication $leave)
    {
        $leave->load(['employee', 'employee.employee.department', 'leaveType', 'approvals.approver']);

        $pdf = Pdf::loadView('pdfs.leave_summary', compact('leave'));

        $fileName = 'leave_summaries/leave_summary_' . $leave->id . '_' . time() . '.pdf';
        \Storage::put('public/' . $fileName, $pdf->output());

        return $fileName;
    }
}
