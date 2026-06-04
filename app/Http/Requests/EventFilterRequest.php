<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventFilterRequest extends FormRequest
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
            'event_type' => ['nullable', 'array'],
            'event_type.*' => ['string', 'max:50'],
            'tab' => ['nullable', 'string', 'in:all,modified,deleted,renamed,moved,offline'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'extension' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}