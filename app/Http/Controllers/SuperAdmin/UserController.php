<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManagedUserRequest;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'active' => ['nullable', 'in:0,1'],
        ]);

        return view('super-admin.users.index', [
            'departments' => Department::query()->orderBy('name')->get(),
            'programs' => Program::query()->with('department')->orderBy('name')->get(),
            'users' => User::query()
                ->with(['roles', 'department', 'student.program', 'lecturer.department'])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query
                    ->where(fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")))
                ->when($filters['role'] ?? null, fn ($query, string $role) => $query
                    ->whereHas('roles', fn ($query) => $query->where('name', $role)))
                ->when(isset($filters['active']), fn ($query) => $query
                    ->where('is_active', (bool) $filters['active']))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function store(StoreManagedUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($data['role'] === UserRole::Student->value
            && ! Program::query()->whereKey($data['program_id'])->whereHas('department', fn ($query) => $query
                ->where('is_active', true))->exists()) {
            throw ValidationException::withMessages([
                'program_id' => 'Students must belong to a program in an active department.',
            ]);
        }

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
                'department_id' => $data['role'] === UserRole::DepartmentAdmin->value
                    ? $data['department_id']
                    : null,
            ]);
            $user->assignRole($data['role']);

            if ($data['role'] === UserRole::Student->value) {
                Student::create([
                    'user_id' => $user->id,
                    'program_id' => $data['program_id'],
                    'student_number' => $data['student_number'],
                    'level' => $data['level'],
                ]);
            } elseif ($data['role'] === UserRole::Lecturer->value) {
                Lecturer::create([
                    'user_id' => $user->id,
                    'department_id' => $data['department_id'],
                    'staff_number' => $data['staff_number'],
                ]);
            }
        });

        return to_route('super-admin.users.index')->with('status', 'User account created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $student = $user->student;
        $lecturer = $user->lecturer;
        $departmentAdmin = $user->hasRole(UserRole::DepartmentAdmin->value);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => ['required', 'boolean'],
        ];

        if ($student) {
            $rules += [
                'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->where('is_active', true)],
                'student_number' => ['required', 'string', 'max:40', Rule::unique('students', 'student_number')->ignore($student->id)],
                'level' => ['required', 'integer', Rule::in([100, 200, 300, 400, 500, 600, 700, 800, 900])],
            ];
        } elseif ($lecturer) {
            $rules += [
                'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
                'staff_number' => ['required', 'string', 'max:40', Rule::unique('lecturers', 'staff_number')->ignore($lecturer->id)],
            ];
        } elseif ($departmentAdmin) {
            $rules['department_id'] = ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)];
        }

        $data = $request->validate($rules);
        $isActive = (bool) $data['is_active'];

        if ($student && (int) $student->program_id !== (int) $data['program_id']
            && $student->courseRegistrations()->exists()) {
            throw ValidationException::withMessages([
                'program_id' => 'A student with registration history cannot be moved to another program.',
            ]);
        }

        if (! $isActive && $user->is($request->user())) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        DB::transaction(function () use ($data, $user, $student, $lecturer, $departmentAdmin): void {
            if (! $data['is_active'] && $user->hasRole(UserRole::SuperAdmin->value)) {
                $activeSuperAdmins = User::query()
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', UserRole::SuperAdmin->value))
                    ->lockForUpdate()
                    ->get(['users.id'])
                    ->count();

                if ($activeSuperAdmins === 1) {
                    throw ValidationException::withMessages([
                        'is_active' => 'At least one active super administrator must remain.',
                    ]);
                }
            }

            $emailChanged = $user->email !== $data['email'];
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
                'department_id' => $departmentAdmin ? $data['department_id'] : null,
            ]);
            if ($emailChanged) {
                $user->email_verified_at = null;
                $user->save();
            }

            if ($student) {
                $student->update([
                    'program_id' => $data['program_id'],
                    'student_number' => $data['student_number'],
                    'level' => $data['level'],
                ]);
            } elseif ($lecturer) {
                $lecturer->update([
                    'department_id' => $data['department_id'],
                    'staff_number' => $data['staff_number'],
                ]);
            }
        });

        return to_route('super-admin.users.index')->with('status', 'User account updated.');
    }
}
