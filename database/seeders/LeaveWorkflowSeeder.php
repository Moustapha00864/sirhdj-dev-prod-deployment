<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Employee;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class LeaveWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles if they don't exist
        $directorRole = Role::firstOrCreate(['name' => 'Director']);
        $hrRole = Role::firstOrCreate(['name' => 'HR Generalist']);
        $managerRole = Role::firstOrCreate(['name' => 'Department Manager']);
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);

        // 2. Create Users
        $password = Hash::make('password');

        // HR (amsathichau@yahoo.fr)
        $hrUser = User::firstOrCreate(
            ['email' => 'amsathichau@yahoo.fr'],
            [
                'name' => 'HR Generalist',
                'password' => $password,
                'type' => 'employee',
                'created_by' => 1, // Assumes admin exists
                'lang' => 'en',
                'status' => 'active'
            ]
        );
        $hrUser->assignRole($hrRole);

        // Director (gueco167@gmail.com)
        $directorUser = User::firstOrCreate(
            ['email' => 'gueco167@gmail.com'],
            [
                'name' => 'Director',
                'password' => $password,
                'type' => 'company', // Directors might be company or employee type, usually top level
                'created_by' => 1,
                'lang' => 'en',
                'status' => 'active'
            ]
        );
        $directorUser->assignRole($directorRole);

        // Manager 1
        $manager1 = User::firstOrCreate(
            ['email' => 'manager1@test.com'],
            [
                'name' => 'Manager 1',
                'password' => $password,
                'type' => 'employee',
                'created_by' => 1,
                'lang' => 'en',
                'status' => 'active'
            ]
        );
        $manager1->assignRole($managerRole);

        // Manager 2 (Validator 2)
        $manager2 = User::firstOrCreate(
            ['email' => 'manager2@test.com'],
            [
                'name' => 'Manager 2',
                'password' => $password,
                'type' => 'employee',
                'created_by' => 1,
                'lang' => 'en',
                'status' => 'active'
            ]
        );
        $manager2->assignRole($managerRole); // Logic says this is validator2, but still a manager

        // Employee
        $employee = User::firstOrCreate(
            ['email' => 'employee@test.com'],
            [
                'name' => 'Test Employee',
                'password' => $password,
                'type' => 'employee',
                'created_by' => 1,
                'lang' => 'en',
                'status' => 'active'
            ]
        );
        $employee->assignRole($employeeRole);


        // 3. Create/Update Branch and Department
        $branch = Branch::firstOrCreate(
            ['name' => 'Main Branch'],
            ['created_by' => 1]
        );

        $department = Department::updateOrCreate(
            ['name' => 'Medical Staff'],
            [
                'branch_id' => $branch->id,
                'manager_id' => $manager1->id,
                'validator2_id' => $manager2->id,
                // director_id is explicitly NOT set as per user request
                'created_by' => 1
            ]
        );

        // 4. Create Employee Records (Required for Department linking)
        $this->createEmployeeRecord($manager1, $department);
        $this->createEmployeeRecord($manager2, $department);
        $this->createEmployeeRecord($employee, $department);
        $this->createEmployeeRecord($hrUser, $department); // HR also in dept usually

        $this->command->info('Leave Workflow Seeder Completed.');
    }

    private function createEmployeeRecord($user, $department)
    {
        if (!Employee::where('user_id', $user->id)->exists()) {
            Employee::create([
                'user_id' => $user->id,
                'employee_id' => 'EMP' . $user->id,
                'department_id' => $department->id,
                'branch_id' => $department->branch_id,
                'date_of_joining' => now(),
                'created_by' => 1
            ]);
        }
    }
}
