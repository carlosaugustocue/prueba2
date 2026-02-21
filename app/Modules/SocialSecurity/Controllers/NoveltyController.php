<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\Novelty;
use App\Modules\SocialSecurity\Requests\StoreNoveltyRequest;
use Illuminate\Http\RedirectResponse;

class NoveltyController extends Controller
{
    /**
     * Registrar una novedad manual para un afiliado.
     */
    public function store(StoreNoveltyRequest $request, Affiliate $affiliate): RedirectResponse
    {
        Novelty::create([
            'affiliate_id' => $affiliate->id,
            'novelty_type_id' => $request->validated('novelty_type_id'),
            'effective_date' => $request->validated('effective_date'),
            'description' => $request->validated('description'),
            'old_value' => $request->validated('old_value'),
            'new_value' => $request->validated('new_value'),
            'registered_by' => $request->user()?->id,
        ]);

        return redirect()->route('affiliates.show', $affiliate)
            ->with('success', 'Novedad registrada correctamente.');
    }
}
