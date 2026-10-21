<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    public function rules(): array
    {
        $price = ['required', 'numeric', 'min:0', 'max:9999.99', 'regex:/^\d{1,4}([.,]\d{1,2})?$/'];

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'sport' => ['required', Rule::in(array_keys(config('courts.sports')))],
            'type' => ['required', Rule::in(array_keys(config('courts.types')))],
            'surface' => ['required', Rule::in(array_keys(config('courts.surfaces')))],
            'enclosure' => ['required', Rule::in(array_keys(config('courts.enclosures')))],
            'status' => ['required', Rule::in(array_keys(config('courts.statuses')))],
            'capacity' => ['required', 'integer', 'between:1,100'],
            'lighting' => ['required', 'boolean'],
            'accessible' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:3000'],
            'hourly_price' => $price,
            'two_hour_price' => $price,
            'three_plus_hour_price' => $price,
            'weekly_hours' => ['required', 'array:1,2,3,4,5,6,7', 'size:7'],
            'weekly_hours.*' => ['present', 'array', 'max:24'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'remove_image' => ['required', 'boolean'],
        ];

        foreach (range(1, 7) as $day) {
            $rules["weekly_hours.$day.*"] = ['required', 'integer', 'between:0,23', 'distinct'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'boolean' => 'El valor de :attribute no es válido.',
            'in' => 'Selecciona una opción válida para :attribute.',
            'min' => 'El campo :attribute no puede ser negativo.',
            'max' => 'El campo :attribute supera el máximo permitido (:max).',
            'between' => 'El campo :attribute debe estar entre :min y :max.',
            'regex' => 'El precio debe tener como máximo dos decimales (por ejemplo, 18,50).',
            'array' => 'La selección de :attribute no es válida.',
            'size' => 'Debes indicar los siete días de la semana.',
            'distinct' => 'No se puede repetir una franja del mismo día.',
            'image.image' => 'Adjunta una imagen válida.',
            'image.mimes' => 'Solo se admiten imágenes JPG, PNG o WebP.',
            'image.max' => 'La imagen no puede superar los 5 MB.',
            'image.dimensions' => 'La imagen no puede superar los 6000 × 6000 píxeles.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre', 'sport' => 'deporte', 'type' => 'tipo de pista',
            'surface' => 'superficie', 'enclosure' => 'cerramiento', 'status' => 'estado',
            'capacity' => 'aforo', 'hourly_price' => 'precio de 1 hora',
            'two_hour_price' => 'precio de 2 horas', 'three_plus_hour_price' => 'precio de 3 o más horas',
            'weekly_hours' => 'horario semanal', 'weekly_hours.*' => 'horario del día',
            'weekly_hours.*.*' => 'franja horaria', 'image' => 'imagen',
        ];
    }
}
