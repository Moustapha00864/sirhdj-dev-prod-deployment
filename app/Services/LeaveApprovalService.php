<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\LeaveApproval;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\LeaveMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LeaveApprovalService
{
    protected $pdfService;

    public function __construct(\App\Services\LeavePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function approve(LeaveApplication $leave, User $approver, string $comments = null)
    {
        return DB::transaction(function () use ($leave, $approver, $comments) {
            $currentStage = $leave->current_stage;
            $department = $leave->employee->employee->department;

            // Validation : verify approver is authorized for this stage
            $this->validateApprover($leave, $approver, $currentStage);

            // Record the approval
            LeaveApproval::create([
                'leave_application_id' => $leave->id,
                'stage' => $currentStage,
                'approver_id' => $approver->id,
                'status' => 'approved',
                'comments' => $comments,
                'actioned_at' => now(),
            ]);

            // Determine next stage
            $nextStage = $this->getNextStage($leave, $currentStage, $department);

            if ($nextStage === 'completed') {
                $leave->update([
                    'status' => 'approved',
                    'is_completed' => true,
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                    'manager_comments' => $comments
                ]);

                // Finalization: create attendance records
                $leave->createAttendanceRecords();

                // Log movement: used leave
                LeaveMovement::create([
                    'leave_balance_id' => $this->getLeaveBalanceId($leave),
                    'type' => 'used',
                    'days' => $leave->total_days,
                    'reason' => 'Leave approved: ' . $leave->id,
                    'created_by' => $approver->id
                ]);

                // Update used_days and remaining_days on balance
                $this->updateLeaveBalance($leave);

                // Generate PDF
                $pdfPath = $this->pdfService->saveSummaryToStorage($leave);
                $fullPdfPath = storage_path('app/public/' . $pdfPath);

                // Send final notification to employee
                Mail::to($leave->employee->email)->send(new \App\Mail\LeaveNotificationMail(
                    $leave,
                    $leave->employee,
                    'employee_final_approved',
                    'Félicitations – Votre demande de congé est validée',
                    null,
                    null,
                    $fullPdfPath
                ));
            } else {
                $leave->update(['current_stage' => $nextStage]);

                // Also notify employee about progress based on what was just approved
                $employeeTemplate = 'employee_level_1_approved';
                $employeeSubject = 'Mise à jour – Votre demande de congé (Niveau 1 validé)';

                if ($currentStage == 2) {
                    $employeeTemplate = 'employee_level_2_approved';
                    $employeeSubject = 'Mise à jour – Votre demande de congé (Niveau 2 validé)';
                } elseif ($currentStage == 3) {
                    $employeeTemplate = 'employee_hr_approved';
                    $employeeSubject = 'Mise à jour – Votre demande de congé (RH validé)';
                }

                Mail::to($leave->employee->email)->send(new \App\Mail\LeaveNotificationMail(
                    $leave,
                    $leave->employee,
                    $employeeTemplate,
                    $employeeSubject
                ));

                // Send notifications based on the new stage (to next approvers)
                $this->notifyNextStageApprovers($leave, $nextStage, $department);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $approver->id,
                'action' => 'leave_approved_stage_' . $currentStage,
                'module' => 'LeaveManagement',
                'target_id' => $leave->id,
                'details' => ['comments' => $comments, 'next_stage' => $nextStage],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return $leave;
        });
    }

    private function getNextStage($leave, $currentStage, $department)
    {
        switch ($currentStage) {
            case 1:
                // Move to Stage 2 if validator2 exists, otherwise skip to Stage 3 (HR)
                return ($department && $department->validator2_id) ? 2 : 3;
            case 2:
                // Stage 2 (Manager 2) always moves to Stage 3 (HR)
                return 3;
            case 3:
                // Stage 3 (HR) moves to Stage 4 (Director)
                return 4;
            case 4:
                // Final stage
                return 'completed';
            default:
                return 'completed';
        }
    }

    private function notifyNextStageApprovers($leave, $nextStage, $department)
    {
        $recipients = [];
        $template = '';
        $subject = '';

        if ($nextStage === 2) {
            if ($department && $department->validator2) {
                $recipients[] = $department->validator2;
                $template = 'manager_level_2';
                $subject = 'Demande de congé – Approbation requise (Niveau 2)';
            }
        } elseif ($nextStage === 3) {
            $hrUsers = User::role('HR Generalist')->get();
            if ($hrUsers->isEmpty()) {
                $hrUsers = User::role('Admin')->get();
            }
            $recipients = $hrUsers;
            $template = 'hr_validation';
            $subject = 'Alerte – Validation RH requise pour une demande de congé';
        } elseif ($nextStage === 4) {
            $recipients = User::role('Director')->get();
            $template = 'director_validation';
            $subject = 'Alerte – Validation Direction requise pour une demande de congé';
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new \App\Mail\LeaveNotificationMail(
                $leave,
                $recipient,
                $template,
                $subject
            ));
        }
    }

    public function reject(LeaveApplication $leave, User $approver, string $comments = null)
    {
        return DB::transaction(function () use ($leave, $approver, $comments) {
            $currentStage = $leave->current_stage;
            $this->validateApprover($leave, $approver, $currentStage);

            LeaveApproval::create([
                'leave_application_id' => $leave->id,
                'stage' => $currentStage,
                'approver_id' => $approver->id,
                'status' => 'rejected',
                'comments' => $comments,
                'actioned_at' => now(),
            ]);

            $leave->update([
                'status' => 'rejected',
                'is_completed' => true,
                'manager_comments' => $comments,
                'approved_by' => $approver->id,
                'approved_at' => now()
            ]);

            // Envoyer notification à l'employé
            Mail::to($leave->employee->email)->send(new \App\Mail\LeaveNotificationMail(
                $leave,
                $leave->employee,
                'employee_reject_approved',
                'Mise à jour – Votre demande de congé (Rejetée)',
                $comments,
                $approver
            ));

            // Journal d’audit
            AuditLog::create([
                'user_id' => $approver->id,
                'action' => 'leave_rejected_stage_' . $currentStage,
                'module' => 'LeaveManagement',
                'target_id' => $leave->id,
                'details' => ['comments' => $comments],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            return $leave;
        });
    }

    private function validateApprover(LeaveApplication $leave, User $approver, int $stage)
    {
        if ($approver->hasRole('Admin')) {
            return true;
        }

        $department = $leave->employee->employee->department;

        switch ($stage) {
            case 1:
                if (!$department || $approver->id !== $department->manager_id) {
                    abort(403, __('Seul le premier validateur peut approuver à l’étape 1'));
                }
                break;
            case 2:
                if (!$department || $approver->id !== $department->validator2_id) {
                    abort(403, __('Seul le second validateur peut approuver à l’étape 2'));
                }
                break;
            case 3:
                if (!$approver->hasRole('HR Generalist')) {
                    abort(403, __('Seuls les RH peuvent approuver à l’étape 3'));
                }
                break;
            case 4:
                if (!$approver->hasRole('Director')) {
                    abort(403, __('Seul le directeur peut approuver à l’étape 4'));
                }
                break;
            default:
                abort(403, __('Étape de validation invalide'));
        }

        return true;
    }

    private function getLeaveBalanceId($leave)
    {
        $balance = \App\Models\LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->first();

        return $balance ? $balance->id : null;
    }

    private function updateLeaveBalance($leave)
    {
        $balance = \App\Models\LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->first();

        if ($balance) {
            $balance->used_days += $leave->total_days;
            $balance->calculateRemainingDays();
            $balance->save();
        }
    }
}
