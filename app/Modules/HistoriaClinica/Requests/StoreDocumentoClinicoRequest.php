<?php

namespace App\Modules\HistoriaClinica\Requests;

use App\Modules\HistoriaClinica\Enums\TipoDocumentoClinico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentoClinicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipos = array_map(fn (\BackedEnum $c) => $c->value, TipoDocumentoClinico::cases());
        $maxSize = 10 * 1024; // 10 MB in KB
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . $maxSize],
            'tipo' => ['required', 'string', Rule::in($tipos)],
            'fecha_documento' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
            'file.max' => 'El archivo no debe superar 10 MB.',
        ];
    }
}
