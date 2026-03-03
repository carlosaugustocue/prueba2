<?php

namespace App\Modules\SocialSecurity\Requests;

use App\Modules\SocialSecurity\Services\ContributionParametersResolver;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'affiliate_id' => ['required', 'exists:affiliates,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'days_worked' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
