<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSize = config('images.max_size_kb');
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                "max:{$maxSize}",
            ],
        ];
    }

    /**
     * custom validator
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $daily_limit = config('images.daily_limit');
            if ($this->user() && $this->user()->hasReachedDailyLimit($daily_limit)) {
                $validator->errors()->add(
                    'image',
                    'Daily upload limit of {$daily_limit} files reached.'
                );
            }
        });
    }
}
