<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('patch');

        $category = $this->route('category');

        return [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',

                Rule::unique('categories', 'name')
                    ->where(
                        fn ($query) => $query->where('user_id', $this->user()->id)
                    )
                    ->ignore($category?->id),
            ],
        ];
    }
}
