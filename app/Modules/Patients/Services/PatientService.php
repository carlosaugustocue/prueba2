<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PatientService
{
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Patient::query()->with(['eps']);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['eps_id'])) {
            $query->where('eps_id', $filters['eps_id']);
        }

        if (! empty($filters['patient_type'])) {
            $query->where('patient_type', $filters['patient_type']);
        }

        return $query->orderBy('first_name')->orderBy('last_name')->paginate($perPage)->withQueryString();
    }

    public function searchForAutocomplete(string $term, int $limit = 10): Collection
    {
        return Patient::query()
            ->with(['eps'])
            ->search($term)
            ->limit($limit)
            ->get(['id', 'uuid', 'document_type', 'document_number', 'first_name', 'last_name', 'phone', 'whatsapp', 'eps_id']);
    }

    public function create(array $data): Patient
    {
        $data['created_by'] = Auth::id();
        return Patient::create($data);
    }

    public function update(Patient $patient, array $data): bool
    {
        $data['updated_by'] = Auth::id();
        return $patient->update($data);
    }
}
