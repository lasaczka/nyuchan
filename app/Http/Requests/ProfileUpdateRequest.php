<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'profile_color' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('nyuchan.profile_colors', []))),
            ],
            'use_tripcode' => ['nullable', 'boolean'],
            'show_name_with_tripcode' => ['nullable', 'boolean'],
            'tripcode_secret' => ['nullable', 'string', 'max:100'],
        ];
    }
}
