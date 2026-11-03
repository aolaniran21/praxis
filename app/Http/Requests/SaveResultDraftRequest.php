<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveResultDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('lecturer');
    }

    public function rules(): array
    {
        return [
            'ca_score' => ['required', 'integer', 'between:0,30'],
            'exam_score' => ['required', 'integer', 'between:0,70'],
        ];
    }
}
