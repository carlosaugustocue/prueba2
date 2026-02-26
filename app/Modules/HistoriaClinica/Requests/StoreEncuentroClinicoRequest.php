<?php

namespace App\Modules\HistoriaClinica\Requests;

use App\Modules\HistoriaClinica\Enums\TipoAtencion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEncuentroClinicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipos = array_map(fn (\BackedEnum $c) => $c->value, TipoAtencion::cases());
        return [
            'tipo_atencion' => ['required', 'string', Rule::in($tipos)],
            'fecha_atencion' => ['required', 'date'],
            'motivo_consulta' => ['required', 'string', 'max:65535'],
            'enfermedad_actual' => ['nullable', 'string', 'max:65535'],
            'estado_mental' => ['nullable', 'string', 'max:65535'],
            'profesional_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
