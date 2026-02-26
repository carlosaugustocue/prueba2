<?php

namespace App\Modules\SocialSecurity\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Affiliate;
use App\Modules\SocialSecurity\Models\OperatorCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OperatorCredentialController extends Controller
{
    public const PROVIDER_LABELS = [
        'PAYMENT_OPERATOR' => 'Operador de pago',
        'ARL' => 'ARL',
        'CCF' => 'CCF',
        'EPS' => 'EPS',
        'AFP' => 'AFP',
    ];

    public function store(Request $request, Affiliate $affiliate): RedirectResponse
    {
        $validated = $request->validate([
            'provider_type' => ['required', 'string', 'in:PAYMENT_OPERATOR,ARL,CCF,EPS,AFP'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:500'],
        ]);

        $existing = OperatorCredential::where('affiliate_id', $affiliate->id)
            ->where('provider_type', $validated['provider_type'])
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Ya existe una credencial para este proveedor. Edítala o elimínala primero.');
        }

        OperatorCredential::create([
            'affiliate_id' => $affiliate->id,
            'provider_type' => $validated['provider_type'],
            'credentials' => [
                'username' => $validated['username'],
                'password' => $validated['password'],
            ],
        ]);

        return redirect()->back()->with('success', 'Credencial guardada correctamente.');
    }

    public function update(Request $request, Affiliate $affiliate, OperatorCredential $operatorCredential): RedirectResponse
    {
        if ($operatorCredential->affiliate_id !== $affiliate->id) {
            abort(404);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
        ]);

        $credentials = $operatorCredential->credentials ?? [];
        $credentials['username'] = $validated['username'];
        if (!empty($validated['password'])) {
            $credentials['password'] = $validated['password'];
        }
        $operatorCredential->credentials = $credentials;
        $operatorCredential->save();

        return redirect()->back()->with('success', 'Credencial actualizada.');
    }

    public function destroy(Affiliate $affiliate, OperatorCredential $operatorCredential): RedirectResponse
    {
        if ($operatorCredential->affiliate_id !== $affiliate->id) {
            abort(404);
        }
        $operatorCredential->delete();
        return redirect()->back()->with('success', 'Credencial eliminada.');
    }
}
