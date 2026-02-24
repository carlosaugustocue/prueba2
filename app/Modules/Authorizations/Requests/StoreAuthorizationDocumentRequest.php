<?php

namespace App\Modules\Authorizations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorizationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = 10 * 1024; // 10 MB in KB
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:' . $maxSize],
            'type' => ['required', 'string', 'in:order_medica,resultados,historia_clinica,otro'],
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
