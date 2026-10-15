<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'title' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],

            'description' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'nullable',
                'string',
            ],

            'category_id' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)
                    ),
            ],

            'due_date' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'nullable',
                'date',
            ],

            'is_completed' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
