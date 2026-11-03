<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('department-admin');
    }

    public function rules(): array
    {
        return [
            'feedback' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
