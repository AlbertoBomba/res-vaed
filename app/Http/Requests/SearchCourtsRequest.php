<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchCourtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date' => $this->input('date', today()->toDateString()),
            'duration' => $this->input('duration', 1),
        ]);
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'sport' => ['nullable', Rule::in(array_keys(config('courts.sports')))],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'duration' => ['required', 'integer', 'between:1,24'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.*' => 'Selecciona una fecha válida a partir de hoy.',
            'duration.*' => 'Selecciona una duración de entre 1 y 24 horas.',
            'company_id.*' => 'Selecciona un club válido.',
            'sport.*' => 'Selecciona un deporte válido.',
        ];
    }
}
