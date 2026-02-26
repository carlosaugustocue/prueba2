<?php

namespace App\Modules\HistoriaClinica\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamenFisicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'peso_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'talla_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'imc' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'presion_arterial_sistolica' => ['nullable', 'integer', 'min:0', 'max:300'],
            'presion_arterial_diastolica' => ['nullable', 'integer', 'min:0', 'max:200'],
            'frecuencia_cardiaca' => ['nullable', 'integer', 'min:0', 'max:300'],
            'frecuencia_respiratoria' => ['nullable', 'integer', 'min:0', 'max:100'],
            'temperatura' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'saturacion_oxigeno' => ['nullable', 'integer', 'min:0', 'max:100'],
            'hallazgos_por_sistema' => ['nullable', 'array'],
            'resumen_general' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
