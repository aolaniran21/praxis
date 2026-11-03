<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('student');
    }

    public function rules(): array
    {
        return [
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['required', 'integer', 'distinct', 'exists:courses,id'],
        ];
    }
}
