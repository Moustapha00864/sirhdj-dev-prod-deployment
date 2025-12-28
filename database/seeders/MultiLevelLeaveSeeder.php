<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeaveSetting;
use App\Models\LeaveBalance;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class MultiLevelLeaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles if they don't exist
        $roles = ['Director', 'HR Generalist', 'Department Manager', 'Employee'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['created_by' => 1] // Assuming ID 1 exists (usually Super Admin)
            );
        }

        // Get Admin user (usually ID 1) for created_by fields
        $admin = User::first() ?? User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'type' => 'company',
            'created_by' => 0
        ]);

        // 2. Create Users

        // HR User
        $hrUser = User::updateOrCreate(
            ['email' => 'amsathichau@yahoo.fr'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('12345678'),
                'type' => 'company', // Or employee depending on system, but usually company level or special employee
                'created_by' => $admin->id,
                'is_enable_login' => 1,
            ]
        );
        $hrUser->assignRole('HR Generalist');

        // Director User
        $directorUser = User::updateOrCreate(
            ['email' => 'director@medistaff.com'],
            [
                'name' => 'Director User',
                'password' => Hash::make('12345678'),
                'type' => 'employee',
                'created_by' => $admin->id,
                'is_enable_login' => 1,
            ]
        );
        $directorUser->assignRole('Director');

        // Manager 1
        $manager1 = User::updateOrCreate(
            ['email' => 'manager1@medistaff.com'],
            [
                'name' => 'Manager One',
                'password' => Hash::make('12345678'),
                'type' => 'employee',
                'created_by' => $admin->id,
                'is_enable_login' => 1,
            ]
        );
        $manager1->assignRole('Department Manager');

        // Manager 2
        $manager2 = User::updateOrCreate(
            ['email' => 'manager2@medistaff.com'],
            [
                'name' => 'Manager Two',
                'password' => Hash::make('12345678'),
                'type' => 'employee',
                'created_by' => $admin->id,
                'is_enable_login' => 1,
            ]
        );
        // Maybe assign a role, but mostly they are identified by department assignment
        $manager2->assignRole('Department Manager');


        // Regular Employee
        $employeeUser = User::updateOrCreate(
            ['email' => 'employee@medistaff.com'],
            [
                'name' => 'Regular Employee',
                'password' => Hash::make('12345678'),
                'type' => 'employee',
                'created_by' => $admin->id,
                'is_enable_login' => 1,
            ]
        );
        $employeeUser->assignRole('Employee');

        // 3. Create Branch and Department
        $branch = Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            ['created_by' => $admin->id]
        );

        $department = Department::updateOrCreate(
            ['name' => 'Medical Staff'],
            [
                'branch_id' => $branch->id,
                'manager_id' => $manager1->id,
                'validator2_id' => $manager2->id,
                'director_id' => $directorUser->id,
                'created_by' => $admin->id,
            ]
        );

        // 4. Create Employee Profiles
        $this->createEmployeeProfile($manager1, $department, 'Manager');
        $this->createEmployeeProfile($manager2, $department, 'Validator');
        $this->createEmployeeProfile($directorUser, $department, 'Director'); // Director linked to dept here for simplicity
        $this->createEmployeeProfile($employeeUser, $department, 'Staff');

        // 5. Leave Configuration

        // Settings
        LeaveSetting::set('default_initial_balance', 20, $admin->id);

        // Leave Type
        $leaveType = LeaveType::updateOrCreate(
            ['name' => 'Congé Annuel'],
            [
                'color' => '#00ff00',
                'max_days_per_year' => 20,
                'created_by' => $admin->id
            ]
        );

        // Leave Policy
        $leavePolicy = LeavePolicy::updateOrCreate(
            ['leave_type_id' => $leaveType->id],
            [
                'name' => 'Politique Standard',
                'max_days_per_year' => 20,
                'min_days_per_application' => 1,
                'max_days_per_application' => 20,
                'created_by' => $admin->id,
                'status' => 'active',
                'requires_approval' => 1
            ]
        );

        // 6. Initialize Balance for Employee
        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $employeeUser->id,
                'leave_type_id' => $leaveType->id,
                'year' => now()->year,
            ],
            [
                'leave_policy_id' => $leavePolicy->id,
                'allocated_days' => 20,
                'used_days' => 0,
                'remaining_days' => 20,
                'initial_leave_balance' => 20,
                'created_by' => $admin->id
            ]
        );

        $this->command->info('Multi-Level Leave Approval Environment Seeded Successfully!');
    }

    private function createEmployeeProfile($user, $department, $designationName)
    {
        // Ensure designation exists
        $designation = \App\Models\Designation::firstOrCreate(
            ['name' => $designationName, 'department_id' => $department->id],
            ['created_by' => 1]
        );

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_id' => 'EMP-' . $user->id,
                // 'name' => $user->name, // Employee name is in User model
                // 'email' => $user->email, // Employee email is in User model
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'date_of_joining' => now(),
                'created_by' => 1,
            ]
        );
    }
}
