<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $query = User::withPermissionCheck()
            ->with(['employee.branch', 'employee.department', 'employee.designation'])
            ->where('type', 'employee');

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhereHas('employee', function ($eq) use ($request) {
                        $eq->where('employee_id', 'like', '%' . $request->search . '%')
                            ->orWhere('phone', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Handle department filter
        if ($request->has('department') && !empty($request->department) && $request->department !== 'all') {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }

        // Handle branch filter
        if ($request->has('branch') && !empty($request->branch) && $request->branch !== 'all') {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            });
        }

        // Handle designation filter
        if ($request->has('designation') && !empty($request->designation) && $request->designation !== 'all') {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('designation_id', $request->designation);
            });
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle employment type filter (Statut)
        if ($request->has('employment_type') && !empty($request->employment_type) && $request->employment_type !== 'all') {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('employment_type', $request->employment_type);
            });
        }

        // Handle personnel category filter
        if ($request->has('personnel') && !empty($request->personnel) && $request->personnel !== 'all') {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('personnel_category', $request->personnel);
            });
        }

        // Handle gender filter
        if ($request->has('gender') && !empty($request->gender) && $request->gender !== 'all') {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Handle date_of_birth filter
        if ($request->has('date_of_birth') && !empty($request->date_of_birth)) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('date_of_birth', $request->date_of_birth);
            });
        }

        // Handle date_of_joining filter
        if ($request->has('date_of_joining') && !empty($request->date_of_joining)) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('date_of_joining', $request->date_of_joining);
            });
        }

        // Handle sorting
        if ($request->has('sort_field') && !empty($request->sort_field)) {
            $query->orderBy($request->sort_field, $request->sort_direction ?? 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $employees = $query->paginate($request->per_page ?? 10);

        // Get branches, departments, and designations for filters
        $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $departments = Department::with('branch')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'branch_id']);

        $designations = Designation::with('department')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'department_id']);


        // Get plan limits for company users and staff users (only in SaaS mode)
        $planLimits = null;

        if (isSaas()) {

            if ($authUser->type === 'company' && $authUser->plan) {
                $currentUserCount = User::where('type', 'employee')->whereIn('created_by', getCompanyAndUsersId())->count();
                $planLimits = [
                    'current_users' => $currentUserCount,
                    'max_users' => $authUser->plan->max_employees,
                    'can_create' => true // <<< LIMITE DÉSACTIVÉE
                ];
            } elseif ($authUser->type !== 'superadmin' && $authUser->created_by) {
                $companyUser = User::find($authUser->created_by);

                if ($companyUser && $companyUser->type === 'company' && $companyUser->plan) {
                    $currentUserCount = User::where('type', 'employee')->whereIn('created_by', getCompanyAndUsersId())->count();
                    $planLimits = [
                        'current_users' => $currentUserCount,
                        'max_users' => $companyUser->plan->max_employees,
                        'can_create' => true // <<< LIMITE DÉSACTIVÉE
                    ];
                }
            }
        }
        if ($planLimits === null) {
            $planLimits = [
                'current_users' => 0,
                'max_users' => 999999,
                'can_create' => true
            ];
        }



        return Inertia::render('hr/employees/index', [
            'employees' => $employees,
            'branches' => $branches,
            'planLimits' => $planLimits,
            'departments' => $departments,
            'designations' => $designations,
            'filters' => $request->all(['search', 'department', 'branch', 'designation', 'status', 'employment_type', 'personnel', 'gender', 'date_of_birth', 'date_of_joining', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get branches, departments, designations, and document types for the form
        $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $departments = Department::with('branch')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'branch_id']);

        $designations = Designation::with('department')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'department_id']);

        $documentTypes = DocumentType::whereIn('created_by', getCompanyAndUsersId())
            ->get(['id', 'name', 'is_required']);

        $shifts = \App\Models\Shift::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'start_time', 'end_time']);

        $attendancePolicies = \App\Models\AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name']);

        return Inertia::render('hr/employees/create', [
            'branches' => $branches,
            'departments' => $departments,
            'designations' => $designations,
            'documentTypes' => $documentTypes,
            'shifts' => $shifts,
            'attendancePolicies' => $attendancePolicies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        try {
            // Validate basic information
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'employee_id' => 'required|string|max:255|unique:employees,employee_id',
                'email' => 'nullable|email|max:255|unique:users,email',
                'password' => 'nullable|string|min:8',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'profile_image' => 'nullable|image|max:2048',
                'shift_id' => 'nullable|exists:shifts,id',
                'attendance_policy_id' => 'nullable|exists:attendance_policies,id',

                // Employment details
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'designation_id' => 'required|exists:designations,id',
                'date_of_joining' => 'required|date',
                'birth_place' => 'nullable|string|max:255',
                'employment_type' => 'required|string|max:50',
                'employment_status' => 'required|string|max:50',
                'personnel_category' => 'nullable|string|max:100',

                // Contact information
                'address_line_1' => 'required|string|max:255',
                'address_line_2' => 'nullable|string|max:255',  // optionnel
                'city' => 'required|string|max:100',
                'state' => 'nullable|string|max:100',           // optionnel
                'country' => 'required|string|max:100',
                'postal_code' => 'nullable|string|max:20',      // optionnel
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_relationship' => 'nullable|string|max:100',
                'emergency_contact_number' => 'nullable|string|max:20',


                // Banking Information
                'bank_name' => 'nullable|string|max:255',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'bank_identifier_code' => 'nullable|string|max:50',
                'bank_branch' => 'nullable|string|max:255',
                'tax_payer_id' => 'nullable|string|max:50',

                // Documents
                'documents' => 'nullable|array',
                'documents.*.document_type_id' => 'nullable|exists:document_types,id',
                'documents.*.file_path' => 'nullable|string',
                'documents.*.expiry_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Create User model object
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->type = 'employee';
            $user->lang = 'en';
            $user->created_by = creatorId();

            // Handle profile image upload for user
            if ($request->has('profile_image')) {
                $user->avatar = $request->profile_image;
            }
            $user->save();

            // Assign Employee role
            $employeeRole = Role::where('name', 'employee')->first();
            if ($employeeRole) {
                $user->assignRole($employeeRole);
            }

            // Create Employee model object
            // Create Employee model object
            $employee = new Employee();
            $employee->user_id = $user->id;
            $employee->employee_id = $request->employee_id;
            $employee->phone = $request->phone;
            $employee->date_of_birth = $request->date_of_birth;
            $employee->birth_place = $request->birth_place ?? null;
            $employee->gender = $request->gender;
            $employee->branch_id = $request->branch_id;
            $employee->department_id = $request->department_id;
            $employee->designation_id = $request->designation_id;
            $employee->date_of_joining = $request->date_of_joining;
            $employee->employment_type = $request->employment_type;
            $employee->personnel_category = $request->personnel_category ?? null;
            $employee->address_line_1 = $request->address_line_1;
            $employee->address_line_2 = $request->address_line_2 ?? null;
            $employee->city = $request->city;
            $employee->state = $request->state ?? null;
            $employee->country = $request->country;
            $employee->postal_code = $request->postal_code ?? null;

            // Contact en cas d'urgence (optionnel)
            $employee->emergency_contact_name = $request->emergency_contact_name ?? null;
            $employee->emergency_contact_relationship = $request->emergency_contact_relationship ?? null;
            $employee->emergency_contact_number = $request->emergency_contact_number ?? null;

            // Banking Information (optionnel)
            $employee->bank_name = $request->bank_name ?? null;
            $employee->account_holder_name = $request->account_holder_name ?? null;
            $employee->account_number = $request->account_number ?? null;
            $employee->bank_identifier_code = $request->bank_identifier_code ?? null;
            $employee->bank_branch = $request->bank_branch ?? null;
            $employee->tax_payer_id = $request->tax_payer_id ?? null;

            // Créé par
            $employee->created_by = creatorId();

            // Enregistrer l'employé
            $employee->save();


            if (!$employee->save()) {
                throw new \Exception('Failed to save employee data');
            }

            // Handle document uploads
            if ($request->has('documents') && is_array($request->documents)) {
                foreach ($request->documents as $document) {
                    if (isset($document['file_path']) && !empty($document['file_path'])) {
                        EmployeeDocument::create([
                            'employee_id' => $employee->user_id,
                            'document_type_id' => $document['document_type_id'],
                            'file_path' => $document['file_path'],
                            'expiry_date' => $document['expiry_date'] ?? null,
                            'verification_status' => 'pending',
                            'created_by' => creatorId(),
                        ]);
                    }
                }
            }

            return redirect()->route('hr.employees.index')->with('success', __('Employee created successfully'));
        } catch (\Exception $e) {
            \Log::error('Employee creation failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', __('Failed to create employee: :message', ['message' => $e->getMessage()]))->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        // Check if employee belongs to current company
        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($employee->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to view this employee'));
        }

        // Load user with employee relationships
        $user = User::with(['employee.branch', 'employee.department', 'employee.designation', 'employee.shift', 'employee.attendancePolicy', 'employee.documents.documentType'])
            ->where('id', $employee->user_id)
            ->first();

        return Inertia::render('hr/employees/show', [
            'employee' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        // Check if employee belongs to current company
        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($employee->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to edit this employee'));
        }

        // Load user with employee relationships
        $user = User::with(['employee.branch', 'employee.department', 'employee.designation', 'employee.documents.documentType'])
            ->where('id', $employee->user_id)
            ->first();

        // Get branches, departments, designations, and document types for the form
        $branches = Branch::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $departments = Department::with('branch')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'branch_id']);

        $designations = Designation::with('department')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'department_id']);

        $documentTypes = DocumentType::whereIn('created_by', getCompanyAndUsersId())
            ->get(['id', 'name', 'is_required']);

        $shifts = \App\Models\Shift::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name', 'start_time', 'end_time']);

        $attendancePolicies = \App\Models\AttendancePolicy::whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->get(['id', 'name']);

        return Inertia::render('hr/employees/edit', [
            'employee' => $user,
            'branches' => $branches,
            'departments' => $departments,
            'designations' => $designations,
            'documentTypes' => $documentTypes,
            'shifts' => $shifts,
            'attendancePolicies' => $attendancePolicies,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        // Check if employee belongs to current company
        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($employee->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to update this employee'));
        }

        try {
            // Validate basic information
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'employee_id' => 'required|string|max:255|unique:employees,employee_id,' . $employee->id,
                'email' => 'nullable|email|max:255|unique:users,email,' . $employee->user_id,
                'password' => 'nullable|string|min:8',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'profile_image' => 'nullable|image|max:2048',
                'shift_id' => 'nullable|exists:shifts,id',
                'attendance_policy_id' => 'nullable|exists:attendance_policies,id',

                // Employment details
                'branch_id' => 'required|exists:branches,id',
                'department_id' => 'required|exists:departments,id',
                'designation_id' => 'required|exists:designations,id',
                'date_of_joining' => 'required|date',
                'birth_place' => 'nullable|string|max:255',
                'employment_type' => 'required|string|max:50',
                'employment_status' => 'required|string|max:50',
                'personnel_category' => 'nullable|string|max:100',

                // Contact information
                'address_line_1' => 'required|string|max:255',
                'address_line_2' => 'nullable|string|max:255',  // optionnel
                'city' => 'required|string|max:100',
                'state' => 'nullable|string|max:100',           // optionnel
                'country' => 'required|string|max:100',
                'postal_code' => 'nullable|string|max:20',      // optionnel
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_relationship' => 'nullable|string|max:100',
                'emergency_contact_number' => 'nullable|string|max:20',

                // Banking Information
                'bank_name' => 'nullable|string|max:255',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:50',
                'bank_identifier_code' => 'nullable|string|max:50',
                'bank_branch' => 'nullable|string|max:255',
                'tax_payer_id' => 'nullable|string|max:50',

                // Documents
                'documents' => 'nullable|array',
                'documents.*.document_type_id' => 'nullable|exists:document_types,id',
                'documents.*.file_path' => 'nullable|string',
                'documents.*.expiry_date' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Get the user
            $user = $employee->user;

            // Update User model object
            $user->name = $request->name;
            $user->email = $request->email;

            // Hash password if provided
            if ($request->has('password') && !empty($request->password)) {
                $user->password = Hash::make($request->password);
            }

            // Handle profile image upload for user
            if ($request->has('profile_image')) {
                $user->avatar = $request->profile_image;
            }

            $user->save();

            // Update Employee model object
            $employee->employee_id = $request->employee_id;
            $employee->shift_id = $request->shift_id;
            $employee->attendance_policy_id = $request->attendance_policy_id;
            $employee->phone = $request->phone;
            $employee->date_of_birth = $request->date_of_birth;
            $employee->birth_place = $request->birth_place ?? null;
            $employee->gender = $request->gender;
            $employee->branch_id = $request->branch_id;
            $employee->department_id = $request->department_id;
            $employee->designation_id = $request->designation_id;
            $employee->date_of_joining = $request->date_of_joining;
            $employee->employment_type = $request->employment_type;
            $employee->personnel_category = $request->personnel_category ?? null;
            $employee->address_line_1 = $request->address_line_1;
            $employee->address_line_2 = $request->address_line_2 ?? null;
            $employee->city = $request->city;
            $employee->state = $request->state ?? null;
            $employee->country = $request->country;
            $employee->postal_code = $request->postal_code ?? null;
            $employee->emergency_contact_name = $request->emergency_contact_name ?? null;
            $employee->emergency_contact_relationship = $request->emergency_contact_relationship ?? null;
            $employee->emergency_contact_number = $request->emergency_contact_number ?? null;
            $employee->bank_name = $request->bank_name ?? null;
            $employee->account_holder_name = $request->account_holder_name ?? null;
            $employee->account_number = $request->account_number ?? null;
            $employee->bank_identifier_code = $request->bank_identifier_code ?? null;
            $employee->bank_branch = $request->bank_branch ?? null;
            $employee->tax_payer_id = $request->tax_payer_id ?? null;

            $employee->save();

            // Handle document uploads
            if ($request->has('documents') && is_array($request->documents)) {
                foreach ($request->documents as $document) {
                    if (isset($document['file_path']) && !empty($document['file_path'])) {
                        EmployeeDocument::create([
                            'employee_id' => $employee->user_id,
                            'document_type_id' => $document['document_type_id'],
                            'file_path' => $document['file_path'],
                            'expiry_date' => $document['expiry_date'] ?? null,
                            'verification_status' => 'pending',
                            'created_by' => creatorId(),
                        ]);
                    }
                }
            }

            return redirect()->route('hr.employees.index')->with('success', __('Employee updated successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update employee'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($userId)
    {
        try {
            $user = User::with('employee')->where('id', $userId)->whereIn('created_by', getCompanyAndUsersId())->first();

            if (!$user || !$user->employee) {
                return redirect()->back()->with('error', __('Employee not found'));
            }

            $employee = $user->employee;

            // Delete documents first
            EmployeeDocument::where('employee_id', $employee->id)->delete();

            // Delete employee record
            $employee->delete();

            // Delete user record and avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->delete();

            return redirect()->route('hr.employees.index')->with('success', __('Employee deleted successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to delete employee: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Update employee status.
     */
    public function toggleStatus(Employee $employee)
    {
        // Check if employee belongs to current company
        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($employee->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to update this employee'));
        }

        try {
            $user = $employee->user;
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            $user->update(['status' => $newStatus]);

            return redirect()->back()->with('success', __('Employee status updated successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update employee status'));
        }
    }

    /**
     * Change employee password.
     */
    public function changePassword(Request $request, Employee $employee)
    {
        // Check if employee belongs to current company
        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($employee->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to change this employee password'));
        }

        try {
            $validated = $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = $employee->user;
            $user->password = Hash::make($validated['password']);
            $user->save();

            return redirect()->back()->with('success', __('Employee password changed successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to change employee password'));
        }
    }

    /**
     * Delete employee document.
     */
    public function deleteDocument($userId, $documentId)
    {
        $user = User::with('employee')->find($userId);

        if (!$user || !$user->employee) {
            return redirect()->back()->with('error', __('Employee not found'));
        }

        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($user->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to access this employee'));
        }

        $document = EmployeeDocument::where('id', $documentId)
            ->where('employee_id', $userId)
            ->first();

        if (!$document) {
            return redirect()->back()->with('error', __('Document not found'));
        }

        try {
            $document->delete();
            return redirect()->back()->with('success', __('Document deleted successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to delete document'));
        }
    }

    /**
     * Approve employee document.
     */
    public function approveDocument($userId, $documentId)
    {
        $user = User::with('employee')->find($userId);
        if (!$user || !$user->employee) {
            return redirect()->back()->with('error', __('Employee not found'));
        }

        $document = EmployeeDocument::where('id', $documentId)
            ->where('employee_id', $userId)
            ->first();

        if (!$document) {
            return redirect()->back()->with('error', __('Document not found'));
        }

        try {
            $document->update(['verification_status' => 'verified']);
            return redirect()->back()->with('success', __('Document approved successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to approve document'));
        }
    }

    /**
     * Reject employee document.
     */
    public function rejectDocument($userId, $documentId)
    {
        $user = User::with('employee')->find($userId);
        if (!$user || !$user->employee) {
            return redirect()->back()->with('error', __('Employee not found'));
        }

        $document = EmployeeDocument::where('id', $documentId)
            ->where('employee_id', $userId)
            ->first();

        if (!$document) {
            return redirect()->back()->with('error', __('Document not found'));
        }

        try {
            $document->update(['verification_status' => 'rejected']);
            return redirect()->back()->with('success', __('Document rejected successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to reject document'));
        }
    }

    /**
     * Download employee document.
     */
    public function downloadDocument($userId, $documentId)
    {

        $user = User::with('employee')->find($userId);
        if (!$user || !$user->employee) {
            return redirect()->back()->with('error', __('Employee not found'));
        }

        $companyUserIds = getCompanyAndUsersId();
        if (!in_array($user->created_by, $companyUserIds)) {
            return redirect()->back()->with('error', __('You do not have permission to access this employee'));
        }

        $document = EmployeeDocument::where('id', $documentId)
            ->where('employee_id', $userId)
            ->first();


        if (!$document) {
            return redirect()->back()->with('error', __('Document not found'));
        }

        if (!$document->file_path) {
            return redirect()->back()->with('error', __('Document file not found'));
        }

        $filePath = getStorageFilePath($document->file_path);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', __('Document file not found'));
        }

        return response()->download($filePath);
    }
}
