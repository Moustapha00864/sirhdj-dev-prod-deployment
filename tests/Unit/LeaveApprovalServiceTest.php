<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Services\LeaveApprovalService;
use App\Mail\LeaveStage1ApprovedMail;
use App\Mail\LeaveStage2ApprovedMail;
use App\Mail\LeaveRejectedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class LeaveApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $admin;
    protected $manager;
    protected $employee;
    protected $hr;
    protected $leaveType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveApprovalService();

        // Create Roles
        Role::create(attributes: ['name' => 'Admin']);
        Role::create(attributes: ['name' => 'HR Generalist']);

        // Create Users manually if factories are tricky
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => 'company'
        ]);
        $this->admin->assignRole('Admin');

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'type' => 'employee'
        ]);

        $this->hr = User::create([
            'name' => 'HR User',
            'email' => 'hr@test.com',
            'password' => bcrypt('password'),
            'type' => 'employee'
        ]);
        $this->hr->assignRole('HR Generalist');

        // Create Department with Manager
        $dept = Department::create([
            'name' => 'IT',
            'manager_id' => $this->manager->id,
            'created_by' => $this->admin->id
        ]);

        // Create Leave Type
        $this->leaveType = LeaveType::create([
            'name' => 'Annual',
            'type' => 'paid',
            'days' => 20,
            'created_by' => $this->admin->id
        ]);

        // Create Employee
        $this->employee = User::create([
            'name' => 'Employee User',
            'email' => 'emp@test.com',
            'password' => bcrypt('password'),
            'type' => 'employee'
        ]);

        Employee::create([
            'user_id' => $this->employee->id,
            'name' => 'Employee User',
            'department_id' => $dept->id,
            'created_by' => $this->admin->id,
            'employee_id' => 'EMP001'
        ]);
    }

    public function test_stage_1_approval_transitions_to_stage_2_and_sends_emails()
    {
        Mail::fake();

        $leave = LeaveApplication::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'total_days' => 3,
            'status' => 'pending',
            'current_stage' => 1,
            'created_by' => $this->employee->id
        ]);

        $this->service->approve($leave, $this->manager, 'Looks good');

        $this->assertEquals(2, $leave->fresh()->current_stage);
        $this->assertEquals('pending', $leave->fresh()->status);

        Mail::assertSent(LeaveStage1ApprovedMail::class);
    }

    public function test_stage_2_approval_completes_workflow_and_sends_final_email()
    {
        Mail::fake();

        $leave = LeaveApplication::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'total_days' => 3,
            'status' => 'pending',
            'current_stage' => 2,
            'created_by' => $this->employee->id
        ]);

        $this->service->approve($leave, $this->hr, 'Final approval');

        $this->assertEquals('approved', $leave->fresh()->status);
        $this->assertTrue($leave->fresh()->is_completed);

        Mail::assertSent(LeaveStage2ApprovedMail::class);
    }

    public function test_rejection_at_any_stage_ends_workflow_and_sends_rejection_email()
    {
        Mail::fake();

        $leave = LeaveApplication::create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'total_days' => 3,
            'status' => 'pending',
            'current_stage' => 1,
            'created_by' => $this->employee->id
        ]);

        $this->service->reject($leave, $this->manager, 'Not a good time');

        $this->assertEquals('rejected', $leave->fresh()->status);
        $this->assertTrue($leave->fresh()->is_completed);

        Mail::assertSent(LeaveRejectedMail::class);
    }
}
