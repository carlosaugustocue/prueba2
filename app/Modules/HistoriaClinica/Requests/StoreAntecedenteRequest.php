<?php

namespace App\Modules\HistoriaClinica\Requests;

use App\Modules\HistoriaClinica\Enums\TipoAntecedente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAntecedenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipos = array_map(fn (\BackedEnum $c) => $c->value, TipoAntecedente::cases());

        return [
            'tipo' => ['required', 'string', Rule::in($tipos)],
            'descripcion' => ['required', 'string', 'max:65535'],
            'fecha_registro' => ['required', 'date'],
            'profesional_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
