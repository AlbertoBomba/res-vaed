<?php

namespace App\Http\Requests;

class ReserveCourtRequest extends SearchCourtsRequest
{
    protected function prepareForValidation(): void
    {
        // A reservation must specify its date and duration explicitly.
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'duration' => ['required', 'integer', 'between:1,24'],
            'start_hour' => ['required', 'integer', 'between:0,23'],
            'quoted_price_cents' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'integer'],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'start_hour.*' => 'Selecciona una hora de inicio válida.',
            'quoted_price_cents.*' => 'Revisa el importe antes de confirmar la reserva.',
        ]);
    }
}
