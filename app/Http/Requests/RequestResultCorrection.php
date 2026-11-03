<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestResultCorrection extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('department-admin');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
