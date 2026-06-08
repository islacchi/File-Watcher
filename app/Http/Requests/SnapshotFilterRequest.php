<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SnapshotFilterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:20'],
            'directory' => ['nullable', 'string', 'max:500'],
            'path' => ['nullable', 'string', 'max:500'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}