<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(UserRole::SuperAdmin->value);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)],
            'department_id' => [
                'nullable',
                'required_if:role,'.UserRole::DepartmentAdmin->value,
                'required_if:role,'.UserRole::Lecturer->value,
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'program_id' => [
                'nullable',
                'required_if:role,'.UserRole::Student->value,
                'integer',
                Rule::exists('programs', 'id')->where('is_active', true),
            ],
            'student_number' => [
                'nullable',
                'required_if:role,'.UserRole::Student->value,
                'string',
                'max:40',
                'unique:students,student_number',
            ],
            'staff_number' => [
                'nullable',
                'required_if:role,'.UserRole::Lecturer->value,
                'string',
                'max:40',
                'unique:lecturers,staff_number',
            ],
            'level' => [
                'nullable',
                'required_if:role,'.UserRole::Student->value,
                'integer',
                'min:100',
                'max:900',
                'multiple_of:100',
            ],
        ];
    }
}
