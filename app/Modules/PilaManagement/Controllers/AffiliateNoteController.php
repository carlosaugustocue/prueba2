<?php

namespace App\Modules\PilaManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PilaManagement\Models\AffiliateNote;
use App\Modules\PilaManagement\Models\PilaAffiliation;
use App\Modules\PilaManagement\Requests\StoreAffiliateNoteRequest;
use App\Modules\PilaManagement\Requests\UpdateAffiliateNoteRequest;
use Illuminate\Http\RedirectResponse;

class AffiliateNoteController extends Controller
{
    public function store(StoreAffiliateNoteRequest $request, PilaAffiliation $affiliation): RedirectResponse
    {
        AffiliateNote::query()->create([
            'affiliate_id' => $affiliation->affiliate_id,
            'type' => $request->string('type')->toString(),
            'content' => $request->string('content')->toString(),
            'is_pinned' => (bool) $request->boolean('is_pinned'),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Nota guardada correctamente.');
    }

    public function update(UpdateAffiliateNoteRequest $request, PilaAffiliation $affiliation, AffiliateNote $note): RedirectResponse
    {
        if ((int) $note->affiliate_id !== (int) $affiliation->affiliate_id) {
            abort(404);
        }

        $note->update([
            'type' => $request->string('type')->toString(),
            'content' => $request->string('content')->toString(),
            'is_pinned' => (bool) $request->boolean('is_pinned'),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Nota actualizada correctamente.');
    }

    public function destroy(PilaAffiliation $affiliation, AffiliateNote $note): RedirectResponse
    {
        if ((int) $note->affiliate_id !== (int) $affiliation->affiliate_id) {
            abort(404);
        }

        $note->delete();

        return redirect()
            ->back()
            ->with('success', 'Nota eliminada correctamente.');
    }
}

